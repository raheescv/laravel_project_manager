<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('show-confirmation', async function(event) {
            const data = event.detail[0];
            if (!data) {
                throw new Error('No data received for confirmation');
            }

            const balance = parseFloat(data.balance) || 0;

            const message = await @this.call('renderConfirmationDialog', data.customer, data.grand_total, data.paid, data.balance, data.payment_methods);

            Swal.fire({
                title: '<i class="fa fa-clipboard-check text-primary"></i> Confirm Sale Transaction',
                html: message,
                icon: null, // Remove default icon since we have custom styling
                width: '600px',
                padding: '20px',
                background: '#ffffff',
                backdrop: ` rgba(0,0,0,0.6) left top no-repeat `,
                showCancelButton: true,
                confirmButtonColor: balance === 0 ? '#28a745' : '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `
                                <i class="fa fa-check-circle" style="color: white;"></i>
                                <span style="color: white;">${balance === 0 ? 'Submit Transaction' : 'Submit Anyway'}</span>
                            `,
                cancelButtonText: `
                                <i class="fa fa-times" style="color: white;"></i>
                                <span style="color: white;">Cancel</span>
                            `,
                buttonsStyling: true,
                reverseButtons: false,
                focusCancel: false,
                customClass: {
                    popup: 'animated bounceIn',
                    confirmButton: 'btn btn-lg px-4 py-2',
                    cancelButton: 'btn btn-lg px-4 py-2'
                },
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
                focusConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        // Add loading state
                        Swal.showLoading();
                        // Call the Livewire method
                        @this.call('save').then(() => {
                            resolve();
                        }).catch((error) => {
                            Swal.showValidationMessage(`Error: ${error.message || 'Something went wrong'}`);
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Transaction has been submitted successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'animated bounceIn'
                        }
                    });
                }
            });
        });
    });
</script>
