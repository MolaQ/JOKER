/**
 * Przycinanie obrazka przed wysłaniem (zdjęcia zawodników, slajdy, logo drużyn i sponsorów...).
 * options: aspect (np. 0.8 dla 4:5, 0 = dowolny kadr), width (px), format ('jpeg' albo 'png' z przezroczystością).
 * Przycięty plik trafia do właściwości komponentu Livewire.
 */
// Kod odpowiedzi serwera na ostatnie wysłanie pliku: Livewire pokazuje tylko ogólny błąd, a kod (np. 401, 403, 413, 500)
// mówi, co poszło nie tak. Status 0 = połączenie przerwane albo zablokowane przed stroną (np. zapora hostingu).
let lastUploadStatus = null;
const originalOpen = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function (method, url, ...rest) {
    if (String(url).includes('/upload-file')) {
        this.addEventListener('loadend', () => { lastUploadStatus = this.status; });
    }

    return originalOpen.call(this, method, url, ...rest);
};

export function registerPhotoCropper() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('photoCropper', (property, options = {}) => ({
            aspect: options.aspect ?? 4 / 5,
            width: options.width ?? 800,
            format: options.format ?? 'jpeg',
            cropper: null,
            source: null,
            uploading: false,
            watchdog: null,
            ready: false,
            error: null,
            original: null,
            tolerance: 40,
            backgroundRemoved: false,
            // Kształt kadru logo przy dowolnych proporcjach: 'free', 'square' (1:1) albo 'circle' (koło, reszta przezroczysta).
            shape: 'free',

            async choose(event) {
                const file = event.target.files[0];
                this.error = null;
                this.stopWatchdog();
                this.uploading = false;

                if (! file) {
                    return;
                }

                if (! file.type.startsWith('image/')) {
                    this.error = document.documentElement.lang === 'en' ? 'Choose an image file (JPG, PNG, WEBP).' : 'Wybierz plik ze zdjęciem (JPG, PNG, WEBP).';

                    return;
                }

                const [{ default: Cropper }] = await Promise.all([
                    import('cropperjs'),
                    import('cropperjs/dist/cropper.css'),
                ]);

                this.destroy();
                this.source = URL.createObjectURL(file);
                this.ready = false;

                await this.$nextTick();

                // Duże zdjęcia z aparatu dekodują się chwilę; Cropper mierzy obrazek przy starcie,
                // więc bez czekania potrafił zbudować kadr o wysokości 100 px z malutkim zdjęciem.
                await this.$refs.image.decode?.().catch(() => {});

                this.cropper = new Cropper(this.$refs.image, {
                    aspectRatio: this.aspect > 0 ? this.aspect : (this.shape === 'free' ? NaN : 1),
                    viewMode: 1,
                    autoCropArea: 1,
                    dragMode: this.aspect > 0 ? 'move' : 'crop',
                    // Kółko myszy przewija okno (np. formularz w oknie dialogowym); powiększanie przyciskami ＋ i －.
                    zoomOnWheel: false,
                    background: false,
                    preview: this.$refs.preview || '',
                    ready: () => {
                        this.ready = true;
                        this.fitToBox(true);
                    },
                });

                // Pole kadrowania zmienia rozmiar (okno dialogowe się otwiera, obrót telefonu): Cropper liczy wymiary od nowa.
                // Bez tego potrafił zostać w rozmiarze 200×100 px, gdy budował się, zanim pole było widoczne.
                this.resizeObserver?.disconnect();
                this.resizeObserver = new ResizeObserver(() => this.fitToBox());
                this.resizeObserver.observe(this.$refs.image.parentElement);
            },

            fitToBox(fresh = false) {
                const box = this.$refs.image?.parentElement;
                const container = this.cropper?.cropper;

                if (! this.ready || ! box || ! container || ! box.offsetWidth) {
                    return;
                }

                if (Math.abs(container.offsetWidth - box.offsetWidth) > 1 || Math.abs(container.offsetHeight - box.offsetHeight) > 1) {
                    this.cropper.resize();

                    // Zaraz po wczytaniu nie ma jeszcze kadru użytkownika: ustawiamy go od nowa na całe pole.
                    if (fresh) {
                        this.cropper.reset();
                    }
                }
            },

            /** Kolejne operacje na logo, liczone od oryginału: 'edge' (tło od brzegów) i {color: [r, g, b]} (kolor w całym logo). */
            ops: [],
            picking: false,
            pickHandler: null,
            resizeObserver: null,

            /** Usuwa jednolite tło logo (np. białe) zalewając od brzegów, więc białe napisy w środku logo zostają. */
            async removeBackground() {
                if (! this.cropper) {
                    return;
                }

                if (! this.ops.includes('edge')) {
                    this.ops.push('edge');
                }

                await this.applyOps();
            },

            /** Tryb pipety: następne kliknięcie w logo wybiera kolor do usunięcia w całym obrazku (np. biały środek liter). */
            startPicking() {
                if (! this.cropper) {
                    return;
                }

                const container = this.cropper.cropper;
                const stop = () => {
                    container.removeEventListener('pointerdown', this.pickHandler, true);
                    container.classList.remove('cursor-crosshair', '[&_*]:!cursor-crosshair');
                    this.pickHandler = null;
                    this.picking = false;
                };

                if (this.picking) {
                    stop();

                    return;
                }

                this.picking = true;
                container.classList.add('cursor-crosshair', '[&_*]:!cursor-crosshair');

                const pick = async (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    stop();

                    const canvas = container.querySelector('.cropper-canvas img');
                    const rect = canvas?.getBoundingClientRect();

                    if (! rect || ! rect.width) {
                        return;
                    }

                    // Kolor bierzemy z tego, co widać (po wcześniejszych operacjach), z uwzględnieniem obrotu.
                    const image = await this.loadImage(this.source);
                    const probe = document.createElement('canvas');
                    probe.width = 1;
                    probe.height = 1;
                    const context = probe.getContext('2d', { willReadFrequently: true });
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    const angle = -((this.cropper.getImageData().rotate || 0) * Math.PI) / 180;
                    const dx = event.clientX - centerX;
                    const dy = event.clientY - centerY;
                    const rx = dx * Math.cos(angle) - dy * Math.sin(angle);
                    const ry = dx * Math.sin(angle) + dy * Math.cos(angle);
                    const imageData = this.cropper.getImageData();
                    const x = Math.floor(((rx + imageData.width / 2) / imageData.width) * image.naturalWidth);
                    const y = Math.floor(((ry + imageData.height / 2) / imageData.height) * image.naturalHeight);

                    if (x < 0 || y < 0 || x >= image.naturalWidth || y >= image.naturalHeight) {
                        return;
                    }

                    context.drawImage(image, x, y, 1, 1, 0, 0, 1, 1);
                    const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;

                    if (a === 0) {
                        return;
                    }

                    this.ops.push({ color: [r, g, b] });
                    await this.applyOps();
                };

                this.pickHandler = pick;
                container.addEventListener('pointerdown', pick, true);
            },

            removeColorOp(index) {
                this.ops.splice(index, 1);
                this.ops.length ? this.applyOps() : this.restoreBackground();
            },

            colorOps() {
                return this.ops.map((op, index) => ({ op, index })).filter(({ op }) => op.color);
            },

            loadImage(url) {
                return new Promise((resolve, reject) => {
                    const element = new Image();
                    element.onload = () => resolve(element);
                    element.onerror = reject;
                    element.src = url;
                });
            },

            /** Liczy logo od oryginału: każda operacja po kolei, z bieżącą czułością (suwak). */
            async applyOps() {
                this.original ??= this.source;
                const image = await this.loadImage(this.original);

                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                const context = canvas.getContext('2d', { willReadFrequently: true });
                context.drawImage(image, 0, 0);

                const { width, height } = canvas;
                const pixels = context.getImageData(0, 0, width, height);
                const data = pixels.data;
                const tolerance = Number(this.tolerance);
                const distanceTo = (reference) => (index) => Math.max(
                    Math.abs(data[index * 4] - reference[0]),
                    Math.abs(data[index * 4 + 1] - reference[1]),
                    Math.abs(data[index * 4 + 2] - reference[2]),
                );
                // Blisko koloru: przezroczyste; na granicy: częściowo, żeby krawędź była gładka.
                const fade = (index, d) => {
                    data[index * 4 + 3] = d <= tolerance ? 0 : Math.min(data[index * 4 + 3], Math.round(((d - tolerance) / tolerance) * 255));
                };

                for (const op of this.ops) {
                    if (op === 'edge') {
                        const corners = [0, width - 1, (height - 1) * width, height * width - 1];
                        const distance = distanceTo([0, 1, 2].map((channel) => corners.reduce((sum, index) => sum + data[index * 4 + channel], 0) / corners.length));
                        const visited = new Uint8Array(width * height);
                        const queue = new Int32Array(width * height);
                        let head = 0;
                        let tail = 0;
                        const push = (index) => {
                            if (! visited[index]) {
                                visited[index] = 1;
                                queue[tail++] = index;
                            }
                        };

                        for (let x = 0; x < width; x++) {
                            push(x);
                            push((height - 1) * width + x);
                        }
                        for (let y = 0; y < height; y++) {
                            push(y * width);
                            push(y * width + width - 1);
                        }

                        while (head < tail) {
                            const index = queue[head++];
                            const d = distance(index);

                            if (d > tolerance * 2 || data[index * 4 + 3] === 0) {
                                continue;
                            }

                            fade(index, d);

                            if (d > tolerance) {
                                continue;
                            }

                            const x = index % width;
                            if (x > 0) push(index - 1);
                            if (x < width - 1) push(index + 1);
                            if (index >= width) push(index - width);
                            if (index < width * (height - 1)) push(index + width);
                        }
                    } else if (op.color) {
                        const distance = distanceTo(op.color);

                        for (let index = 0; index < width * height; index++) {
                            if (data[index * 4 + 3] === 0) {
                                continue;
                            }

                            const d = distance(index);

                            if (d <= tolerance * 2) {
                                fade(index, d);
                            }
                        }
                    }
                }

                context.putImageData(pixels, 0, 0);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

                if (this.source && this.source !== this.original) {
                    URL.revokeObjectURL(this.source);
                }

                this.source = URL.createObjectURL(blob);
                // Ten sam rozmiar: Cropper zostawia kadr na miejscu (i nie zgłasza ponownie „ready”).
                this.cropper.replace(this.source, true);
                this.backgroundRemoved = this.ops.length > 0;
            },

            restoreBackground() {
                this.ops = [];

                if (! this.original || ! this.cropper) {
                    this.backgroundRemoved = false;

                    return;
                }

                URL.revokeObjectURL(this.source);
                this.source = this.original;
                this.original = null;
                this.cropper.replace(this.source, true);
                this.backgroundRemoved = false;
            },

            setShape(shape) {
                this.shape = shape;
                this.cropper?.setAspectRatio(shape === 'free' ? NaN : 1);
            },

            rotate(degrees) {
                this.cropper?.rotate(degrees);
            },

            zoom(ratio) {
                this.cropper?.zoom(ratio);
            },

            confirm() {
                if (! this.cropper) {
                    return;
                }

                this.uploading = true;

                const png = this.format === 'png';
                const size = this.aspect > 0
                    ? { width: this.width, height: Math.round(this.width / this.aspect) }
                    : (this.shape === 'free' ? { maxWidth: this.width, maxHeight: this.width } : { width: this.width, height: this.width });

                let canvas = this.cropper.getCroppedCanvas({ ...size, imageSmoothingQuality: 'high', ...(png ? {} : { fillColor: '#fff' }) });

                if (this.shape === 'circle') {
                    const round = document.createElement('canvas');
                    round.width = canvas.width;
                    round.height = canvas.height;
                    const context = round.getContext('2d');
                    context.beginPath();
                    context.arc(round.width / 2, round.height / 2, Math.min(round.width, round.height) / 2, 0, Math.PI * 2);
                    context.closePath();
                    context.clip();
                    context.drawImage(canvas, 0, 0);
                    canvas = round;
                }

                lastUploadStatus = null;

                const failed = (message) => {
                    this.stopWatchdog();
                    this.uploading = false;
                    const code = lastUploadStatus === null ? '' : ` (${document.documentElement.lang === 'en' ? 'code' : 'kod'} ${lastUploadStatus})`;
                    this.error = message ?? (document.documentElement.lang === 'en' ? `Could not upload the photo${code}. Please try again.` : `Nie udało się wysłać zdjęcia${code}. Spróbuj ponownie.`);
                };

                // Gdy serwer nie odpowie (np. wygasła sesja albo błąd po stronie serwera), Livewire nie woła żadnej
                // funkcji zwrotnej i przycisk zostawał na zawsze w stanie „Wysyłanie…”.
                this.watchdog = setTimeout(() => failed(document.documentElement.lang === 'en'
                    ? 'The server did not respond. Refresh the page and try again.'
                    : 'Serwer nie odpowiedział. Odśwież stronę (F5) i spróbuj ponownie.'), 45000);

                canvas.toBlob((blob) => {
                    if (! blob) {
                        failed();

                        return;
                    }

                    const file = new File([blob], png ? 'obrazek.png' : 'zdjecie.jpg', { type: png ? 'image/png' : 'image/jpeg' });

                    try {
                        this.$wire.upload(property, file, () => {
                            this.stopWatchdog();
                            this.uploading = false;
                            this.destroy();
                        }, () => failed());
                    } catch (exception) {
                        failed();
                    }
                }, png ? 'image/png' : 'image/jpeg', 0.9);
            },

            stopWatchdog() {
                clearTimeout(this.watchdog);
                this.watchdog = null;
            },

            destroy() {
                this.resizeObserver?.disconnect();
                this.resizeObserver = null;
                this.cropper?.destroy();
                this.cropper = null;

                [this.source, this.original].filter(Boolean).forEach((url) => URL.revokeObjectURL(url));

                this.source = null;
                this.original = null;
                this.backgroundRemoved = false;
                this.ops = [];
                this.picking = false;
                this.ready = false;
                this.shape = 'free';

                if (this.$refs.input) {
                    this.$refs.input.value = '';
                }
            },
        }));
    });
}
