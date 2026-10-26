/**
 * Przycinanie obrazka przed wysłaniem (zdjęcia zawodników, slajdy, logo drużyn i sponsorów...).
 * options: aspect (np. 0.8 dla 4:5, 0 = dowolny kadr), width (px), format ('jpeg' albo 'png' z przezroczystością).
 * Przycięty plik trafia do właściwości komponentu Livewire.
 */
export function registerPhotoCropper() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('photoCropper', (property, options = {}) => ({
            aspect: options.aspect ?? 4 / 5,
            width: options.width ?? 800,
            format: options.format ?? 'jpeg',
            cropper: null,
            source: null,
            uploading: false,
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

                this.cropper = new Cropper(this.$refs.image, {
                    aspectRatio: this.aspect > 0 ? this.aspect : (this.shape === 'free' ? NaN : 1),
                    viewMode: 1,
                    autoCropArea: 1,
                    dragMode: this.aspect > 0 ? 'move' : 'crop',
                    background: false,
                    preview: this.$refs.preview || '',
                    ready: () => { this.ready = true; },
                });
            },

            /**
             * Usuwa jednolite tło logo (np. białe): zalewa od brzegów obrazka piksele podobne do koloru w rogach,
             * więc białe napisy w środku logo zostają. Krawędź dostaje miękkie przejście zamiast „schodków”.
             */
            async removeBackground() {
                if (! this.cropper) {
                    return;
                }

                this.original ??= this.source;
                const image = await new Promise((resolve, reject) => {
                    const element = new Image();
                    element.onload = () => resolve(element);
                    element.onerror = reject;
                    element.src = this.original;
                });

                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                const context = canvas.getContext('2d', { willReadFrequently: true });
                context.drawImage(image, 0, 0);

                const { width, height } = canvas;
                const pixels = context.getImageData(0, 0, width, height);
                const data = pixels.data;
                const corners = [0, width - 1, (height - 1) * width, height * width - 1];
                const reference = [0, 1, 2].map((channel) => corners.reduce((sum, index) => sum + data[index * 4 + channel], 0) / corners.length);
                const tolerance = Number(this.tolerance);
                const distance = (index) => Math.max(
                    Math.abs(data[index * 4] - reference[0]),
                    Math.abs(data[index * 4 + 1] - reference[1]),
                    Math.abs(data[index * 4 + 2] - reference[2]),
                );

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

                    // Blisko koloru tła: przezroczyste; na granicy: częściowo, żeby krawędź była gładka.
                    data[index * 4 + 3] = d <= tolerance ? 0 : Math.min(data[index * 4 + 3], Math.round(((d - tolerance) / tolerance) * 255));

                    if (d > tolerance) {
                        continue;
                    }

                    const x = index % width;
                    if (x > 0) push(index - 1);
                    if (x < width - 1) push(index + 1);
                    if (index >= width) push(index - width);
                    if (index < width * (height - 1)) push(index + width);
                }

                context.putImageData(pixels, 0, 0);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

                if (this.source && this.source !== this.original) {
                    URL.revokeObjectURL(this.source);
                }

                this.source = URL.createObjectURL(blob);
                this.ready = false;
                this.cropper.replace(this.source);
                this.backgroundRemoved = true;
            },

            restoreBackground() {
                if (! this.original || ! this.cropper) {
                    return;
                }

                URL.revokeObjectURL(this.source);
                this.source = this.original;
                this.original = null;
                this.ready = false;
                this.cropper.replace(this.source);
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

                canvas.toBlob((blob) => {
                    const file = new File([blob], png ? 'obrazek.png' : 'zdjecie.jpg', { type: png ? 'image/png' : 'image/jpeg' });

                    this.$wire.upload(property, file, () => {
                        this.uploading = false;
                        this.destroy();
                    }, () => {
                        this.uploading = false;
                        this.error = document.documentElement.lang === 'en' ? 'Could not upload the photo. Please try again.' : 'Nie udało się wysłać zdjęcia. Spróbuj ponownie.';
                    });
                }, png ? 'image/png' : 'image/jpeg', 0.9);
            },

            destroy() {
                this.cropper?.destroy();
                this.cropper = null;

                [this.source, this.original].filter(Boolean).forEach((url) => URL.revokeObjectURL(url));

                this.source = null;
                this.original = null;
                this.backgroundRemoved = false;
                this.ready = false;
                this.shape = 'free';

                if (this.$refs.input) {
                    this.$refs.input.value = '';
                }
            },
        }));
    });
}
