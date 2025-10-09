<script src="{{ asset('js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: 'textarea.tinyEditor', // Replace this CSS selector to match the placeholder element for TinyMCE
        plugins: 'code table lists image',
        toolbar: 'undo redo | formatselect| bold italic | image | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table',
        images_upload_url: '{{ route("admin.image-upload") }}',
        images_upload_base_path: '{{ asset("storage") }}/uploads/',
        relative_urls: false,
        convert_urls: false,
        file_picker_types: 'image',
        file_picker_callback: function (cb, value, meta) {
            // Customize the file picker if needed
            // You can open a file picker dialog here
        },
        automatic_uploads: true,
        images_upload_handler: function (blobInfo, success, failure) {
            return new Promise((resolve, reject) => {
                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                fetch('{{ route("admin.image-upload") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result && result.location) {
                            resolve(result.location);
                        } else {
                            reject('Image upload failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error uploading image:', error);
                        reject('Image upload failed');
                    });
            });
        },
    });
</script>
