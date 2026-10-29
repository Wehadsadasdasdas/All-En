const tabs = document.querySelectorAll('.tab');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Here you would normally show/hide content based on the selected tab
            // For this example, we'll just show an alert
            if (this.id === 'tab-details') {
                alert('Product Details tab selected');
            } else if (this.id === 'tab-faqs') {
                alert('FAQs tab');
            }
        });
    });