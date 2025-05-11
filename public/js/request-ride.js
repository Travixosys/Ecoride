document.addEventListener('DOMContentLoaded', function() {
    const rideRequestForm = document.getElementById('rideRequestForm');
    
    if (rideRequestForm) {
        rideRequestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                pickup_location: document.getElementById('pickup_location').value,
                dropoff_location: document.getElementById('dropoff_location').value,
                passenger_count: parseInt(document.getElementById('passenger_count').value)
            };
            
            // Debug info
            console.log('Submitting ride request:', formData);
            
            fetch('/request-ride', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('Ride request submitted successfully!');
                    // Reset form
                    rideRequestForm.reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your request. Please try again.');
            });
        });
    } else {
        console.error('Ride request form not found');
    }
});