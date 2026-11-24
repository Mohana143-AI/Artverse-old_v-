document.addEventListener('DOMContentLoaded', function() {
    // Reveal animations on scroll
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, observerOptions);

    // Add elements to observer if we had a specific class, 
    // for now we'll just manually add fade-in-up to elements in PHP.
    
    // Optional: Add some interactivity to category pills
    const pills = document.querySelectorAll('.category-pill');
    pills.forEach(pill => {
        pill.addEventListener('click', function(e) {
            if(this.tagName !== 'A') {
                e.preventDefault();
                pills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
});
