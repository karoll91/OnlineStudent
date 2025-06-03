/**
 * Online Student Registration System - Main JavaScript
 */

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {

    // Initialize tooltips
    initializeTooltips();

    // Form validation
    initializeFormValidation();

    // Auto-hide alerts
    autoHideAlerts();

    // Initialize quiz functionality if on quiz page
    if (document.querySelector('.quiz-container')) {
        initializeQuiz();
    }

    // Initialize dashboard if on dashboard page
    if (document.querySelector('.dashboard-container')) {
        initializeDashboard();
    }

    // Smooth scrolling for anchor links
    initializeSmoothScrolling();
});

// Initialize Bootstrap tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Form validation
function initializeFormValidation() {
    // Bootstrap form validation
    var forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Password strength checker
    const passwordInput = document.querySelector('input[type="password"][name="password"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }

    // Confirm password validation
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    }
}

// Password strength checker
function checkPasswordStrength() {
    const password = this.value;
    const strengthIndicator = document.querySelector('.password-strength');

    if (!strengthIndicator) return;

    let strength = 0;
    const checks = [
        password.length >= 8,
        /[a-z]/.test(password),
        /[A-Z]/.test(password),
        /[0-9]/.test(password),
        /[^a-zA-Z0-9]/.test(password)
    ];

    strength = checks.filter(Boolean).length;

    strengthIndicator.className = 'password-strength';

    if (strength < 3) {
        strengthIndicator.classList.add('weak');
        strengthIndicator.textContent = 'Weak';
    } else if (strength < 5) {
        strengthIndicator.classList.add('medium');
        strengthIndicator.textContent = 'Medium';
    } else {
        strengthIndicator.classList.add('strong');
        strengthIndicator.textContent = 'Strong';
    }
}

// Validate password match
function validatePasswordMatch() {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = this.value;

    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
}

// Auto-hide alerts after 5 seconds
function autoHideAlerts() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
}

// Quiz functionality
function initializeQuiz() {
    let timeRemaining = 0;
    let timerInterval = null;

    // Start quiz timer
    function startTimer(duration) {
        timeRemaining = duration;
        updateTimerDisplay();

        timerInterval = setInterval(function() {
            timeRemaining--;
            updateTimerDisplay();

            // Warning states
            const timerElement = document.querySelector('.quiz-timer');
            if (timeRemaining <= 300) { // 5 minutes
                timerElement.classList.add('danger');
            } else if (timeRemaining <= 600) { // 10 minutes
                timerElement.classList.add('warning');
            }

            // Time's up
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                submitQuiz();
            }
        }, 1000);
    }

    // Update timer display
    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        const timerDisplay = document.querySelector('.timer-display');
        if (timerDisplay) {
            timerDisplay.textContent = display;
        }
    }

    // Handle option selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.option-card')) {
            const optionCard = e.target.closest('.option-card');
            const questionCard = optionCard.closest('.question-card');

            // Remove selection from other options
            questionCard.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Select current option
            optionCard.classList.add('selected');

            // Check radio button
            const radio = optionCard.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }

            // Update progress
            updateQuizProgress();
        }
    });

    // Update quiz progress
    function updateQuizProgress() {
        const totalQuestions = document.querySelectorAll('.question-card').length;
        const answeredQuestions = document.querySelectorAll('.option-card.selected').length;
        const progressPercent = (answeredQuestions / totalQuestions) * 100;

        const progressBar = document.querySelector('.quiz-progress-bar');
        if (progressBar) {
            progressBar.style.width = progressPercent + '%';
        }

        const progressText = document.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `${answeredQuestions}/${totalQuestions} answered`;
        }
    }

    // Submit quiz
    function submitQuiz() {
        if (confirm('Are you sure you want to submit your quiz?')) {
            const form = document.querySelector('#quiz-form');
            if (form) {
                form.submit();
            }
        }
    }

    // Initialize timer if exists
    const timerElement = document.querySelector('.quiz-timer');
    if (timerElement && timerElement.dataset.duration) {
        startTimer(parseInt(timerElement.dataset.duration));
    }

    // Submit button
    const submitButton = document.querySelector('.submit-quiz');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            submitQuiz();
        });
    }
}

// Dashboard functionality
function initializeDashboard() {
    // Animate counters
    animateCounters();

    // Load recent activities
    loadRecentActivities();

    // Chart initialization if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
}

// Animate number counters
function animateCounters() {
    const counters = document.querySelectorAll('.counter');

    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        let current = 0;
        const increment = target / 100;

        const updateCounter = setInterval(() => {
            current += increment;
            counter.textContent = Math.floor(current);

            if (current >= target) {
                counter.textContent = target;
                clearInterval(updateCounter);
            }
        }, 20);
    });
}

// Load recent activities (AJAX)
function loadRecentActivities() {
    const activitiesContainer = document.querySelector('.recent-activities');
    if (!activitiesContainer) return;

    fetch('ajax/get-activities.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayActivities(data.activities);
            }
        })
        .catch(error => {
            console.error('Error loading activities:', error);
        });
}

// Display activities
function displayActivities(activities) {
    const container = document.querySelector('.recent-activities');
    if (!container) return;

    container.innerHTML = '';

    activities.forEach(activity => {
        const activityElement = document.createElement('div');
        activityElement.className = 'activity-item d-flex align-items-center mb-3';
        activityElement.innerHTML = `
            <div class="activity-icon me-3">
                <i class="fas fa-${activity.icon} text-primary"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <small class="text-muted">${activity.time_ago}</small>
            </div>
        `;
        container.appendChild(activityElement);
    });
}

// Initialize charts
function initializeCharts() {
    // Progress Chart
    const progressChart = document.querySelector('#progressChart');
    if (progressChart) {
        new Chart(progressChart, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Score Trend Chart
    const scoreChart = document.querySelector('#scoreChart');
    if (scoreChart) {
        new Chart(scoreChart, {
            type: 'line',
            data: {
                labels: ['Quiz 1', 'Quiz 2', 'Quiz 3', 'Quiz 4', 'Quiz 5'],
                datasets: [{
                    label: 'Score',
                    data: [75, 82, 78, 85, 90],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
}

// Smooth scrolling for anchor links
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility functions
const Utils = {
    // Show loading state
    showLoading: function (element) {
        element.classList.add('loading');
        element.disabled = true;
    }
}