/**
 * CityCare Interactive House Tour System
 * Character-driven onboarding walkthrough with animations
 */

// Tour Configuration (mirrored from PHP tours.php)
const TOUR_CONFIG = {
    shadows: {
        name: 'Shadows',
        title: 'Shadows Tour',
        color: '#2d3748',
        accent: '#4a5568',
        personality: 'strategic',
        iconFolder: 'Shadow_Walking',
        intro: [
            'Hello, I\'m Shadows.',
            'Strategic. Methodical. Always thinking ahead.',
            'Let me show you how to navigate CityCare with precision.',
        ],
        steps: [
            {
                target: '.navbar',
                position: 'bottom',
                title: 'The Command Center',
                message: 'The navigation bar is your control center. Here you\'ll find quick access to all features.',
                dialogStyle: 'Think of it as your mission control.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Submit a Report',
                message: 'Every report matters. Whether it\'s a pothole or a safety concern, you can submit it here with precision.',
                dialogStyle: 'Strategy starts with accurate information.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Track Your Reports',
                message: 'Monitor your reports in real-time. See how authorities respond, how they prioritize, and what the timeline looks like.',
                dialogStyle: 'Knowledge is power.',
            },
            {
                target: 'footer',
                position: 'top',
                title: 'Stay Connected',
                message: 'Find contact information and support here. We\'re in this together.',
                dialogStyle: 'Every shadow has a light source.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Ready to Begin',
                message: 'You\'re prepared now. Go forth and report strategically.',
                dialogStyle: 'The city needs informed citizens.',
            },
        ],
        outro: [
            'You\'re ready now.',
            'Remember: precision and strategy always win.',
            'Welcome to CityCare.',
        ],
    },
    hipsters: {
        name: 'Hipsters',
        title: 'Hipsters Tour',
        color: '#d946a6',
        accent: '#ec4899',
        personality: 'creative',
        iconFolder: 'Hipster_Walking',
        intro: [
            'Hey, it\'s me, Hipsters!',
            'Creative. Trendsetter. Design-focused.',
            'Let\'s make reporting issues look and feel amazing.',
        ],
        steps: [
            {
                target: '.navbar',
                position: 'bottom',
                title: 'Your Vibe Check Starts Here',
                message: 'The navbar is like the cover of your favorite indie album. Clean, stylish, and intuitive. That\'s the vibe.',
                dialogStyle: 'Aesthetics matter, always.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Express Yourself',
                message: 'Reporting is self-expression. Tell us what you see, what you feel, what matters to you. Be authentic.',
                dialogStyle: 'Your voice deserves to be heard.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Creative Community',
                message: 'You\'re part of a community making cities better. Every report is a brushstroke on the canvas of change.',
                dialogStyle: 'We\'re all artists here.',
            },
            {
                target: 'footer',
                position: 'top',
                title: 'Connect With Us',
                message: 'Find us in the footer. Links, socials, all the good stuff. Stay in the loop.',
                dialogStyle: 'Community is everything.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Let\'s Make an Impact',
                message: 'You\'re all set! Time to make your mark. Report with style.',
                dialogStyle: 'Be the change you want to see.',
            },
        ],
        outro: [
            'You\'ve got this!',
            'Remember: every report is a design choice.',
            'Welcome to our creative community.',
        ],
    },
    engineers: {
        name: 'Engineers',
        title: 'Engineers Tour',
        color: '#16a34a',
        accent: '#22c55e',
        personality: 'technical',
        iconFolder: 'Engineer_Walking',
        intro: [
            'Greetings. I\'m Engineers.',
            'Technical. Precise. Systematic.',
            'Let me walk you through the technical architecture of CityCare.',
        ],
        steps: [
            {
                target: '.navbar',
                position: 'bottom',
                title: 'Navigation System',
                message: 'The navbar is your primary user interface layer. All navigation routes are optimized for usability and performance.',
                dialogStyle: 'Structure enables functionality.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Report Submission Module',
                message: 'Use the submission form to create comprehensive reports. Include location, category, and detailed description for maximum data quality.',
                dialogStyle: 'Accurate inputs produce accurate outputs.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Dashboard Analytics',
                message: 'Your dashboard displays real-time metrics: report status, priority levels, and authority responses. Data-driven insights.',
                dialogStyle: 'Measure twice, report once.',
            },
            {
                target: 'footer',
                position: 'top',
                title: 'Support & Documentation',
                message: 'Technical support and API documentation available in the footer. All systems documented.',
                dialogStyle: 'Well-documented systems scale better.',
            },
            {
                target: 'body',
                position: 'center',
                title: 'System Ready',
                message: 'All systems initialized and operational. You\'re ready to interface with CityCare.',
                dialogStyle: 'System check complete.',
            },
        ],
        outro: [
            'All systems nominal.',
            'Remember: data integrity is paramount.',
            'Welcome to the system.',
        ],
    },
    speedsters: {
        name: 'Speedsters',
        title: 'Speedsters Tour',
        color: '#dc2626',
        accent: '#ef4444',
        personality: 'energetic',
        iconFolder: 'Speedster_Walking',
        intro: [
            'Yo, yo, yo! It\'s Speedsters here!',
            'Fast, energetic, always on the move!',
            'Buckle up—this tour\'s gonna be lightning quick and absolutely electric!',
        ],
        steps: [
            {
                target: '.navbar',
                position: 'bottom',
                title: 'Go, Go, Go!',
                message: 'This is the navbar—your fast track to everything! Click, navigate, move fast. No time to waste!',
                dialogStyle: 'Speed wins the race!',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Report in a Flash!',
                message: 'Got an issue? Boom! Submit it right here! The faster you report, the faster we can fix it. Let\'s move!',
                dialogStyle: 'Time is everything!',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Real-Time Updates!',
                message: 'Your dashboard is screaming with real-time data! Watch reports get assigned, prioritized, and resolved at lightning speed!',
                dialogStyle: 'Action-packed and alive!',
            },
            {
                target: 'footer',
                position: 'top',
                title: 'Keep Connected!',
                message: 'Footer\'s got all your quick links—support, contact, everything you need. Fast access, all the time!',
                dialogStyle: 'Stay connected and keep moving!',
            },
            {
                target: 'body',
                position: 'center',
                title: 'Let\'s Gooooooo!',
                message: 'You\'re ready to roll! Time to make a difference at warp speed! Let\'s change this city FAST!',
                dialogStyle: 'Momentum is everything!',
            },
        ],
        outro: [
            'You\'re unstoppable now!',
            'Remember: speed and action make the difference!',
            'Welcome to the fast lane!',
        ],
    },
};

/**
 * Main Tour Manager Class
 */
class TourManager {
    constructor() {
        this.currentTour = null;
        this.currentStep = 0;
        this.isActive = false;
        this.characterElement = null;
        this.dialogElement = null;
        this.overlayElement = null;
    }

    /**
     * Start a tour by house name
     */
    startTour(houseName) {
        houseName = houseName.toLowerCase();
        
        if (!TOUR_CONFIG[houseName]) {
            console.error(`Tour not found: ${houseName}`);
            return;
        }

        this.currentTour = TOUR_CONFIG[houseName];
        this.currentStep = 0;
        this.isActive = true;

        // Initialize tour UI
        this.createCharacterBubble();
        this.createOverlay();
        
        // Show intro
        this.showIntro();
    }

    /**
     * Create the character bubble and dialog container
     */
    createCharacterBubble() {
        // Remove existing if any
        if (this.characterElement) {
            this.characterElement.remove();
        }

        const container = document.createElement('div');
        container.id = 'tour-character-container';
        container.className = 'tour-character-container';
        
        // Character avatar
        const character = document.createElement('div');
        character.className = 'tour-character-avatar';
        character.innerHTML = `
            <div class="character-image-wrapper">
                <img src="${this.getCharacterImagePath(0)}" alt="Character" class="character-image" />
            </div>
        `;

        // Dialog bubble
        const dialog = document.createElement('div');
        dialog.className = 'tour-dialog-bubble';
        dialog.style.borderColor = this.currentTour.color;
        dialog.innerHTML = `
            <div class="dialog-header" style="background-color: ${this.currentTour.color}; color: white;">
                <span class="dialog-title">${this.currentTour.name}</span>
                <button class="dialog-close-btn" aria-label="Close tour">&times;</button>
            </div>
            <div class="dialog-content">
                <p class="dialog-message"></p>
                <p class="dialog-style"></p>
            </div>
            <div class="dialog-footer">
                <button class="tour-btn-prev btn-sm">← Back</button>
                <span class="step-counter"></span>
                <button class="tour-btn-next btn-sm">Next →</button>
            </div>
        `;

        // Store references
        this.characterElement = character;
        this.dialogElement = dialog;

        container.appendChild(character);
        container.appendChild(dialog);
        document.body.appendChild(container);

        // Attach event listeners
        this.attachDialogEvents();
    }

    /**
     * Create overlay for highlighting elements
     */
    createOverlay() {
        if (this.overlayElement) {
            this.overlayElement.remove();
        }

        this.overlayElement = document.createElement('div');
        this.overlayElement.id = 'tour-overlay';
        this.overlayElement.className = 'tour-overlay';
        this.overlayElement.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        document.body.appendChild(this.overlayElement);
    }

    /**
     * Show intro dialog
     */
    showIntro() {
        const introText = this.currentTour.intro;
        let messageIndex = 0;

        const showNextMessage = () => {
            if (messageIndex < introText.length) {
                this.updateDialog(
                    introText[messageIndex],
                    `${this.currentTour.name} says:`,
                    false,
                    true,
                    () => {
                        messageIndex++;
                        showNextMessage();
                    }
                );
            } else {
                // Intro complete, start actual tour
                this.showStep(0);
            }
        };

        showNextMessage();
    }

    /**
     * Show a specific step
     */
    showStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= this.currentTour.steps.length) {
            this.endTour();
            return;
        }

        this.currentStep = stepIndex;
        const step = this.currentTour.steps[stepIndex];

        // Scroll to the target element first
        this.scrollToElement(step.target);

        // Highlight target element
        this.highlightElement(step.target);

        // Update dialog
        this.updateDialog(
            step.message,
            step.title,
            true,
            false,
            null,
            stepIndex,
            this.currentTour.steps.length
        );

        // Animate character
        this.animateCharacter('walk-in');
    }

    /**
     * Update dialog content and buttons
     */
    updateDialog(message, title, showButtons, autoNext, callback, stepIndex, totalSteps) {
        const messageEl = this.dialogElement.querySelector('.dialog-message');
        const styleEl = this.dialogElement.querySelector('.dialog-style');
        const titleEl = this.dialogElement.querySelector('.dialog-title');
        const footerEl = this.dialogElement.querySelector('.dialog-footer');
        const prevBtn = this.dialogElement.querySelector('.tour-btn-prev');
        const nextBtn = this.dialogElement.querySelector('.tour-btn-next');
        const counterEl = this.dialogElement.querySelector('.step-counter');

        // Animate in
        this.dialogElement.classList.add('dialog-enter');

        messageEl.textContent = message;
        titleEl.textContent = title || this.currentTour.name;
        styleEl.textContent = '';

        if (showButtons) {
            footerEl.style.display = 'flex';
            prevBtn.style.display = stepIndex > 0 ? 'block' : 'none';
            counterEl.textContent = `${stepIndex + 1} / ${totalSteps}`;
            counterEl.style.display = 'inline-block';
            nextBtn.textContent = stepIndex === totalSteps - 1 ? 'Finish' : 'Next →';
        } else {
            footerEl.style.display = 'none';
        }

        if (autoNext && callback) {
            setTimeout(callback, 3000);
        }
    }

    /**
     * Scroll to an element on the page smoothly
     */
    scrollToElement(selector) {
        const element = document.querySelector(selector);
        if (!element) return;

        // Scroll element into view with smooth behavior, centered in viewport
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    /**
     * Highlight a target element on the page
     */
    highlightElement(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            // If element not found, just show general guide
            this.overlayElement.style.pointerEvents = 'none';
            return;
        }

        const rect = element.getBoundingClientRect();
        
        // Create highlight box
        const highlight = document.createElement('div');
        highlight.className = 'tour-highlight';
        highlight.style.position = 'fixed';
        highlight.style.top = `${rect.top - 5}px`;
        highlight.style.left = `${rect.left - 5}px`;
        highlight.style.width = `${rect.width + 10}px`;
        highlight.style.height = `${rect.height + 10}px`;
        highlight.style.zIndex = '9999';
        highlight.style.border = `3px solid ${this.currentTour.color}`;
        highlight.style.borderRadius = '8px';
        highlight.style.pointerEvents = 'none';
        highlight.style.animation = 'tourPulse 2s infinite';

        // Remove old highlight
        const oldHighlight = document.querySelector('.tour-highlight');
        if (oldHighlight) oldHighlight.remove();

        document.body.appendChild(highlight);

        // Position character based on element position
        this.positionCharacter(rect);
    }

    /**
     * Position character near the target element
     */
    positionCharacter(elementRect) {
        if (!this.characterElement) return;

        // Position in bottom right by default
        const bottom = window.innerHeight - elementRect.bottom - 20;
        const right = window.innerWidth - elementRect.right - 20;

        this.characterElement.parentElement.style.position = 'fixed';
        this.characterElement.parentElement.style.bottom = Math.max(20, bottom) + 'px';
        this.characterElement.parentElement.style.right = Math.max(20, right) + 'px';
        this.characterElement.parentElement.style.zIndex = '10000';
    }

    /**
     * Animate character
     */
    animateCharacter(animation) {
        if (!this.characterElement) return;

        const img = this.characterElement.querySelector('.character-image');
        if (!img) return;

        // Cycle through walking frames
        if (animation === 'walk-in') {
            let frame = 0;
            const interval = setInterval(() => {
                img.src = this.getCharacterImagePath(frame);
                frame = (frame + 1) % 4;
                // Stop after 2 seconds
                if (frame === 0) {
                    clearInterval(interval);
                }
            }, 300);
        }
    }

    /**
     * Get character image path (cycles through frames)
     */
    getCharacterImagePath(frameIndex = 0) {
        // Get base path from window or config
        const basePath = window.BASE_PATH || '/ChampionTrials2';
        const folder = this.currentTour.iconFolder;
        const imagePath = `${basePath}/public/assets/images/${folder}/character_${frameIndex}.png`;
        
        return imagePath;
    }

    /**
     * Attach event listeners to dialog buttons
     */
    attachDialogEvents() {
        const prevBtn = this.dialogElement.querySelector('.tour-btn-prev');
        const nextBtn = this.dialogElement.querySelector('.tour-btn-next');
        const closeBtn = this.dialogElement.querySelector('.dialog-close-btn');

        prevBtn?.addEventListener('click', () => this.previousStep());
        nextBtn?.addEventListener('click', () => this.nextStep());
        closeBtn?.addEventListener('click', () => this.endTour());
    }

    /**
     * Go to next step
     */
    nextStep() {
        if (this.currentStep < this.currentTour.steps.length - 1) {
            this.showStep(this.currentStep + 1);
        } else {
            this.showOutro();
        }
    }

    /**
     * Go to previous step
     */
    previousStep() {
        if (this.currentStep > 0) {
            this.showStep(this.currentStep - 1);
        }
    }

    /**
     * Show outro dialog
     */
    showOutro() {
        const outroText = this.currentTour.outro;
        let messageIndex = 0;

        const showNextMessage = () => {
            if (messageIndex < outroText.length) {
                this.updateDialog(
                    outroText[messageIndex],
                    `${this.currentTour.name} says:`,
                    false,
                    true,
                    () => {
                        messageIndex++;
                        showNextMessage();
                    }
                );
            } else {
                // Outro complete
                this.endTour();
            }
        };

        showNextMessage();
    }

    /**
     * End the tour
     */
    endTour() {
        this.isActive = false;

        // Fade out
        if (this.characterElement) {
            this.characterElement.parentElement.classList.add('fade-out');
        }

        setTimeout(() => {
            if (this.characterElement) {
                this.characterElement.parentElement.remove();
            }
            if (this.overlayElement) {
                this.overlayElement.remove();
            }
            const highlight = document.querySelector('.tour-highlight');
            if (highlight) {
                highlight.remove();
            }
            this.currentTour = null;
        }, 300);
    }
}

// Global tour manager instance
let tourManager = null;

/**
 * Initialize tour system
 */
function initializeTourSystem() {
    tourManager = new TourManager();

    // Attach click handlers to tour options in navbar
    document.addEventListener('click', function (e) {
        if (e.target.closest('.tour-option')) {
            e.preventDefault();
            const houseName = e.target.closest('.tour-option').getAttribute('data-tour');
            startTour(houseName);
        }
    });
}

/**
 * Start a tour (public API)
 */
function startTour(houseName) {
    if (!tourManager) {
        initializeTourSystem();
    }

    // Close any active tour
    if (tourManager.isActive) {
        tourManager.endTour();
    }

    // Start new tour
    tourManager.startTour(houseName);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTourSystem);
} else {
    initializeTourSystem();
}

// Export for external use
window.startTour = startTour;
window.TourManager = TourManager;
