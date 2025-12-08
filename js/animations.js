document.addEventListener('DOMContentLoaded', function() {
    const animateElements = () => {
        const mainContent = document.querySelector('main > div');
        if (mainContent) {
            mainContent.classList.add('fade-in');
        }
        
        const forms = document.querySelectorAll('form');
        forms.forEach((form, index) => {
            form.classList.add('slide-in-left');
            form.style.animationDelay = `${index * 0.1}s`;
        });
        
        const headings = document.querySelectorAll('main h1, main h2');
        headings.forEach((heading, index) => {
            heading.classList.add('fade-in-up');
            heading.style.animationDelay = `${index * 0.2}s`;
        });
    };
    
    animateElements();
    
    const requestForm = document.getElementById('requestForm');
    if (requestForm) {
        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        progressBar.innerHTML = '<div class="progress-fill"></div>';
        requestForm.insertBefore(progressBar, requestForm.firstElementChild);
        
        const inputs = requestForm.querySelectorAll('input[required], textarea[required]');
        const progressFill = progressBar.querySelector('.progress-fill');
        
        function updateProgress() {
            const filled = Array.from(inputs).filter(input => input.value.trim() !== '').length;
            const progress = (filled / inputs.length) * 100;
            progressFill.style.width = `${progress}%`;
        }
        
        inputs.forEach(input => {
            input.addEventListener('input', updateProgress);
            input.addEventListener('change', updateProgress);
        });
        
        updateProgress();
    }
    
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('input[type="submit"], button[type="submit"]');
            if (!submitBtn) return;
            
            const originalText = submitBtn.value || submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            
            if (submitBtn.tagName === 'INPUT') {
                submitBtn.value = 'Отправка...';
            } else {
                submitBtn.innerHTML = '<span class="loading-spinner"></span> Отправка...';
            }

            setTimeout(() => {
                showNotification('Данные успешно отправлены!', 'success');
                
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                
                if (submitBtn.tagName === 'INPUT') {
                    submitBtn.value = originalText;
                } else {
                    submitBtn.textContent = originalText;
                }
                
                if (form.id === 'feedbackForm') {
                    setTimeout(() => {
                        form.reset();
                        if (progressBar) {
                            updateProgress();
                        }
                    }, 1000);
                }
            }, 2000);
        });
    });
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            this.classList.toggle('active');
            const content = this.nextElementSibling;
            content.classList.toggle('active');
        });
    });
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    const currentDateElement = document.getElementById('currentDate');
    if (currentDateElement) {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            timeZone: 'Europe/Moscow'
        };
        currentDateElement.textContent = now.toLocaleDateString('ru-RU', options);
    }
    
    const weightInput = document.querySelector('input[name="weightFreight"]');
    const volumeInput = document.querySelector('input[name="volumeFreight"]');
    const costInput = document.querySelector('input[name="costFreight"]');
    
    if (weightInput && volumeInput && costInput) {
        function calculateEstimate() {
            const weight = parseFloat(weightInput.value) || 0;
            const volume = parseFloat(volumeInput.value) || 0;
            
            if (weight === 0 && volume === 0) {
                costInput.placeholder = 'Введите вес или объём';
                return;
            }
            
            let estimate = 0;
            if (weight > 0) estimate += weight * 50;
            if (volume > 0) estimate += volume * 1500;
            
            estimate = Math.max(estimate, 1500);
            
            costInput.placeholder = `Примерно: ${Math.round(estimate).toLocaleString('ru-RU')} руб.`;
        }
        
        weightInput.addEventListener('input', calculateEstimate);
        volumeInput.addEventListener('input', calculateEstimate);
        
        calculateEstimate();
    }
});

const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    @keyframes fadeOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    
    .notification.hiding {
        animation: fadeOutUp 0.3s ease-out forwards;
    }
`;
document.head.appendChild(notificationStyle);