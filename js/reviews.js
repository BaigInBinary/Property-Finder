document.addEventListener('DOMContentLoaded', function() {
    // Rating Input Functionality
    const ratingInput = document.querySelector('.rating-input');
    const ratingStars = ratingInput.querySelectorAll('i');
    let selectedRating = 0;

    function updateStars(rating) {
        ratingStars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }

    ratingInput.addEventListener('mouseover', (e) => {
        if (e.target.matches('i')) {
            const rating = parseInt(e.target.dataset.rating);
            updateStars(rating);
        }
    });

    ratingInput.addEventListener('mouseleave', () => {
        updateStars(selectedRating);
    });

    ratingInput.addEventListener('click', (e) => {
        if (e.target.matches('i')) {
            selectedRating = parseInt(e.target.dataset.rating);
            updateStars(selectedRating);
        }
    });

    // Review Form Submission
    const reviewForm = document.getElementById('reviewForm');
    const reviewText = document.getElementById('reviewText');

    reviewForm.addEventListener('submit', (e) => {
        e.preventDefault();

        // Validate rating
        if (selectedRating === 0) {
            alert('Please select a rating');
            return;
        }

        // Validate review text
        if (reviewText.value.length < 50) {
            alert('Please write at least 50 characters in your review');
            return;
        }

        // Create new review
        const newReview = createReviewElement({
            rating: selectedRating,
            text: reviewText.value,
            user: {
                name: 'Current User', // This would come from the logged-in user's data
                avatar: 'https://placehold.co/50x50'
            },
            timestamp: new Date()
        });

        // Add new review to the list
        const reviewsList = document.querySelector('.reviews-list');
        reviewsList.insertBefore(newReview, reviewsList.firstChild);

        // Reset form
        reviewForm.reset();
        selectedRating = 0;
        updateStars(0);

        // Show success message
        showAlert('success', 'Your review has been posted successfully!');
    });

    // Review Actions (Edit/Delete)
    document.querySelector('.reviews-list').addEventListener('click', (e) => {
        if (e.target.matches('.edit-review')) {
            handleEditReview(e.target.closest('.review-item'));
        } else if (e.target.matches('.delete-review')) {
            handleDeleteReview(e.target.closest('.review-item'));
        }
    });

    // Filter Reviews
    const reviewsFilter = document.querySelector('.reviews-filter select');
    reviewsFilter.addEventListener('change', (e) => {
        const value = e.target.value;
        const reviews = Array.from(document.querySelectorAll('.review-item'));
        
        reviews.sort((a, b) => {
            if (value === 'newest') {
                return new Date(b.dataset.timestamp) - new Date(a.dataset.timestamp);
            } else if (value === 'highest') {
                return b.dataset.rating - a.dataset.rating;
            } else {
                return a.dataset.rating - b.dataset.rating;
            }
        });

        const reviewsList = document.querySelector('.reviews-list');
        reviews.forEach(review => reviewsList.appendChild(review));
    });

    // Load More Reviews
    const loadMoreBtn = document.getElementById('loadMoreReviews');
    loadMoreBtn.addEventListener('click', loadMoreReviews);

    // Helper Functions
    function createReviewElement(review) {
        const div = document.createElement('div');
        div.className = 'review-item new';
        div.dataset.rating = review.rating;
        div.dataset.timestamp = review.timestamp;

        const stars = Array(5).fill('').map((_, i) => 
            `<i class="fas fa-star${i >= review.rating ? '-empty' : ''}"></i>`
        ).join('');

        div.innerHTML = `
            <div class="d-flex">
                <img src="${review.user.avatar}" alt="User" class="rounded-circle me-3">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">${review.user.name}</h5>
                            <div class="stars mb-2">
                                ${stars}
                            </div>
                        </div>
                        <div class="review-actions">
                            <button class="btn btn-link btn-sm edit-review">Edit</button>
                            <button class="btn btn-link btn-sm text-danger delete-review">Delete</button>
                        </div>
                    </div>
                    <p class="review-text">${review.text}</p>
                    <small class="text-muted">Posted just now</small>
                </div>
            </div>
        `;

        return div;
    }

    function handleEditReview(reviewElement) {
        const reviewText = reviewElement.querySelector('.review-text');
        const originalText = reviewText.textContent;
        
        // Create edit form
        const editForm = document.createElement('form');
        editForm.innerHTML = `
            <div class="mb-3">
                <textarea class="form-control" rows="4" required>${originalText}</textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm me-2">Save</button>
            <button type="button" class="btn btn-outline-secondary btn-sm">Cancel</button>
        `;

        // Replace text with form
        reviewText.replaceWith(editForm);

        // Handle form submission
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const newText = editForm.querySelector('textarea').value;
            
            if (newText.length < 50) {
                alert('Please write at least 50 characters');
                return;
            }

            const newTextElement = document.createElement('p');
            newTextElement.className = 'review-text';
            newTextElement.textContent = newText;
            editForm.replaceWith(newTextElement);
            
            showAlert('success', 'Your review has been updated!');
        });

        // Handle cancel
        editForm.querySelector('button[type="button"]').addEventListener('click', () => {
            const newTextElement = document.createElement('p');
            newTextElement.className = 'review-text';
            newTextElement.textContent = originalText;
            editForm.replaceWith(newTextElement);
        });
    }

    function handleDeleteReview(reviewElement) {
        if (confirm('Are you sure you want to delete this review?')) {
            reviewElement.style.opacity = '0';
            setTimeout(() => {
                reviewElement.remove();
                showAlert('success', 'Review deleted successfully!');
            }, 300);
        }
    }

    function loadMoreReviews() {
        // This would typically fetch more reviews from the server
        // For demo purposes, we'll just add some dummy reviews
        const dummyReviews = [
            {
                rating: 4,
                text: 'Great property with excellent amenities. The location is perfect for both work and leisure.',
                user: {
                    name: 'Jane Smith',
                    avatar: 'https://placehold.co/50x50'
                },
                timestamp: new Date(Date.now() - 86400000) // 1 day ago
            },
            {
                rating: 5,
                text: 'Absolutely loved my stay here! The views are breathtaking and the service is impeccable.',
                user: {
                    name: 'Mike Johnson',
                    avatar: 'https://placehold.co/50x50'
                },
                timestamp: new Date(Date.now() - 172800000) // 2 days ago
            }
        ];

        const reviewsList = document.querySelector('.reviews-list');
        dummyReviews.forEach(review => {
            const reviewElement = createReviewElement(review);
            reviewsList.appendChild(reviewElement);
        });

        // Disable load more button after loading all reviews
        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = 'No More Reviews';
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
}); 