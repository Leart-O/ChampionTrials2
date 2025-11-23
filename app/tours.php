<?php
/**
 * Tour Configuration and Data
 * Defines character personalities and tour content
 */

/**
 * Character Personalities and Tour Data
 * Each character has a unique voice and personality
 */
$TOURS = [
    'shadows' => [
        'name' => 'Shadows',
        'title' => 'Shadows Tour',
        'color' => '#2d3748',
        'accent' => '#4a5568',
        'personality' => 'strategic',
        'description' => 'Strategic, thoughtful, mysterious guide',
        'icon_folder' => 'Shadow_Walking',
        'house_logo' => 'shadow-shield.svg', // or use image from Houses/
        'intro' => [
            'Hello, I\'m Shadows.',
            'Strategic. Methodical. Always thinking ahead.',
            'Let me show you how to navigate CityCare with precision.',
        ],
        'steps' => [
            [
                'target' => '.navbar',
                'position' => 'bottom',
                'title' => 'The Command Center',
                'message' => 'The navigation bar is your control center. Here you\'ll find quick access to all features.',
                'dialogStyle' => 'Think of it as your mission control.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Submit a Report',
                'message' => 'Every report matters. Whether it\'s a pothole or a safety concern, you can submit it here with precision.',
                'dialogStyle' => 'Strategy starts with accurate information.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Track Your Reports',
                'message' => 'Monitor your reports in real-time. See how authorities respond, how they prioritize, and what the timeline looks like.',
                'dialogStyle' => 'Knowledge is power.',
            ],
            [
                'target' => 'footer',
                'position' => 'top',
                'title' => 'Stay Connected',
                'message' => 'Find contact information and support here. We\'re in this together.',
                'dialogStyle' => 'Every shadow has a light source.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Ready to Begin',
                'message' => 'You\'re prepared now. Go forth and report strategically.',
                'dialogStyle' => 'The city needs informed citizens.',
            ],
        ],
        'outro' => [
            'You\'re ready now.',
            'Remember: precision and strategy always win.',
            'Welcome to CityCare.',
        ],
    ],
    'hipsters' => [
        'name' => 'Hipsters',
        'title' => 'Hipsters Tour',
        'color' => '#d946a6',
        'accent' => '#ec4899',
        'personality' => 'creative',
        'description' => 'Creative, stylish, trendy guide',
        'icon_folder' => 'Hipster_Walking',
        'house_logo' => 'hipster-shield.svg',
        'intro' => [
            'Hey, it\'s me, Hipsters!',
            'Creative. Trendsetter. Design-focused.',
            'Let\'s make reporting issues look and feel amazing.',
        ],
        'steps' => [
            [
                'target' => '.navbar',
                'position' => 'bottom',
                'title' => 'Your Vibe Check Starts Here',
                'message' => 'The navbar is like the cover of your favorite indie album. Clean, stylish, and intuitive. That\'s the vibe.',
                'dialogStyle' => 'Aesthetics matter, always.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Express Yourself',
                'message' => 'Reporting is self-expression. Tell us what you see, what you feel, what matters to you. Be authentic.',
                'dialogStyle' => 'Your voice deserves to be heard.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Creative Community',
                'message' => 'You\'re part of a community making cities better. Every report is a brushstroke on the canvas of change.',
                'dialogStyle' => 'We\'re all artists here.',
            ],
            [
                'target' => 'footer',
                'position' => 'top',
                'title' => 'Connect With Us',
                'message' => 'Find us in the footer. Links, socials, all the good stuff. Stay in the loop.',
                'dialogStyle' => 'Community is everything.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Let\'s Make an Impact',
                'message' => 'You\'re all set! Time to make your mark. Report with style.',
                'dialogStyle' => 'Be the change you want to see.',
            ],
        ],
        'outro' => [
            'You\'ve got this!',
            'Remember: every report is a design choice.',
            'Welcome to our creative community.',
        ],
    ],
    'engineers' => [
        'name' => 'Engineers',
        'title' => 'Engineers Tour',
        'color' => '#16a34a',
        'accent' => '#22c55e',
        'personality' => 'technical',
        'description' => 'Technical, precise, methodical guide',
        'icon_folder' => 'Engineer_Walking',
        'house_logo' => 'engineer-shield.svg',
        'intro' => [
            'Greetings. I\'m Engineers.',
            'Technical. Precise. Systematic.',
            'Let me walk you through the technical architecture of CityCare.',
        ],
        'steps' => [
            [
                'target' => '.navbar',
                'position' => 'bottom',
                'title' => 'Navigation System',
                'message' => 'The navbar is your primary user interface layer. All navigation routes are optimized for usability and performance.',
                'dialogStyle' => 'Structure enables functionality.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Report Submission Module',
                'message' => 'Use the submission form to create comprehensive reports. Include location, category, and detailed description for maximum data quality.',
                'dialogStyle' => 'Accurate inputs produce accurate outputs.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Dashboard Analytics',
                'message' => 'Your dashboard displays real-time metrics: report status, priority levels, and authority responses. Data-driven insights.',
                'dialogStyle' => 'Measure twice, report once.',
            ],
            [
                'target' => 'footer',
                'position' => 'top',
                'title' => 'Support & Documentation',
                'message' => 'Technical support and API documentation available in the footer. All systems documented.',
                'dialogStyle' => 'Well-documented systems scale better.',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'System Ready',
                'message' => 'All systems initialized and operational. You\'re ready to interface with CityCare.',
                'dialogStyle' => 'System check complete.',
            ],
        ],
        'outro' => [
            'All systems nominal.',
            'Remember: data integrity is paramount.',
            'Welcome to the system.',
        ],
    ],
    'speedsters' => [
        'name' => 'Speedsters',
        'title' => 'Speedsters Tour',
        'color' => '#dc2626',
        'accent' => '#ef4444',
        'personality' => 'energetic',
        'description' => 'Fast-talking, energetic, motivational guide',
        'icon_folder' => 'Speedster_Walking',
        'house_logo' => 'speedster-shield.svg',
        'intro' => [
            'Yo, yo, yo! It\'s Speedsters here!',
            'Fast, energetic, always on the move!',
            'Buckle up—this tour\'s gonna be lightning quick and absolutely electric!',
        ],
        'steps' => [
            [
                'target' => '.navbar',
                'position' => 'bottom',
                'title' => 'Go, Go, Go!',
                'message' => 'This is the navbar—your fast track to everything! Click, navigate, move fast. No time to waste!',
                'dialogStyle' => 'Speed wins the race!',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Report in a Flash!',
                'message' => 'Got an issue? Boom! Submit it right here! The faster you report, the faster we can fix it. Let\'s move!',
                'dialogStyle' => 'Time is everything!',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Real-Time Updates!',
                'message' => 'Your dashboard is screaming with real-time data! Watch reports get assigned, prioritized, and resolved at lightning speed!',
                'dialogStyle' => 'Action-packed and alive!',
            ],
            [
                'target' => 'footer',
                'position' => 'top',
                'title' => 'Keep Connected!',
                'message' => 'Footer\'s got all your quick links—support, contact, everything you need. Fast access, all the time!',
                'dialogStyle' => 'Stay connected and keep moving!',
            ],
            [
                'target' => 'body',
                'position' => 'center',
                'title' => 'Let\'s Gooooooo!',
                'message' => 'You\'re ready to roll! Time to make a difference at warp speed! Let\'s change this city FAST!',
                'dialogStyle' => 'Momentum is everything!',
            ],
        ],
        'outro' => [
            'You\'re unstoppable now!',
            'Remember: speed and action make the difference!',
            'Welcome to the fast lane!',
        ],
    ],
];

/**
 * Get tour data by house name
 */
function getTourData($houseName) {
    global $TOURS;
    $houseName = strtolower($houseName);
    return isset($TOURS[$houseName]) ? $TOURS[$houseName] : null;
}

/**
 * Get all available tours
 */
function getAllTours() {
    global $TOURS;
    return array_keys($TOURS);
}

/**
 * Get tour color (for UI styling)
 */
function getTourColor($houseName) {
    $tour = getTourData($houseName);
    return $tour ? $tour['color'] : '#000000';
}
