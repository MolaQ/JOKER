/**
 * Przycinanie zdjęcia zawodnika do pionowego kadru 4:5 z podglądem na żywo.
 * Przycięty plik (800×1000 JPG) trafia do właściwości komponentu Livewire.
 */
export function registerPhotoCropper() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('photoCropper', (property) => ({
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
                    this.error = 'Wybierz plik ze zdjęciem (JPG, PNG, WEBP).';

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
                    aspectRatio: 4 / 5,
                    viewMode: 1,
                    autoCropArea: 1,
                    dragMode: 'move',
                    background: false,
                    preview: this.$refs.preview,
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

                this.cropper.getCroppedCanvas({ width: 800, height: 1000, imageSmoothingQuality: 'high', fillColor: '#fff' }).toBlob((blob) => {
                    const file = new File([blob], 'zdjecie.jpg', { type: 'image/jpeg' });

                    this.$wire.upload(property, file, () => {
                        this.uploading = false;
                        this.destroy();
                    }, () => {
                        this.uploading = false;
                        this.error = 'Nie udało się wysłać zdjęcia. Spróbuj ponownie.';
                    });
                }, 'image/jpeg', 0.9);
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
