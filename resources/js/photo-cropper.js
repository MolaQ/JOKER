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
                    aspectRatio: this.aspect > 0 ? this.aspect : NaN,
                    viewMode: 1,
                    autoCropArea: 1,
                    dragMode: this.aspect > 0 ? 'move' : 'crop',
                    background: false,
                    preview: this.$refs.preview || '',
                    ready: () => { this.ready = true; },
                });
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
                    : { maxWidth: this.width, maxHeight: this.width };

                this.cropper.getCroppedCanvas({ ...size, imageSmoothingQuality: 'high', ...(png ? {} : { fillColor: '#fff' }) }).toBlob((blob) => {
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

                if (this.source) {
                    URL.revokeObjectURL(this.source);
                }

                this.source = null;
                this.ready = false;

                if (this.$refs.input) {
                    this.$refs.input.value = '';
                }
            },
        }));
    });
}
