# CityCare House Tour System - Implementation Guide

## Overview

The House Tour system is a character-driven onboarding walkthrough that guides new users through the CityCare platform. Each of the four "houses" (Shadows, Hipsters, Engineers, Speedsters) has a unique personality and teaching style.

## System Components

### 1. **app/tours.php** - Tour Configuration
Contains all tour data, character personalities, dialog content, and step definitions.

**Key Functions:**
- `getTourData($houseName)` - Retrieve tour configuration for a specific house
- `getAllTours()` - Get list of available tour houses
- `getTourColor($houseName)` - Get house color for UI styling

### 2. **public/assets/js/tours.js** - Tour Logic & Manager
Implements the `TourManager` class which handles:
- Tour initialization and lifecycle
- Character animation and positioning
- Dialog bubble management
- Element highlighting and focus
- Step progression (next/previous)
- Intro and outro sequences

**Key Classes:**
```javascript
class TourManager {
    startTour(houseName)      // Start a tour
    showStep(stepIndex)       // Show a specific step
    nextStep()                // Advance to next step
    previousStep()            // Go back to previous step
    endTour()                 // End the tour cleanly
    createCharacterBubble()   // Create UI elements
    highlightElement(selector) // Highlight target elements
    animateCharacter(animation) // Animate character movement
}
```

**Global Function:**
```javascript
startTour(houseName) // Public API to start a tour
```

### 3. **public/assets/css/tours.css** - Styling
Complete styling for:
- Character avatar container
- Dialog bubble with header/footer
- Overlay and highlight effects
- Animations (slide, bounce, fade, pulse)
- Responsive design
- Dark mode support

### 4. **includes/navbar.php** - House Tours Dropdown
Added dropdown menu in navbar with:
- Four tour options (one for each house)
- House color indicators
- Click handlers to start tours

## How It Works

### 1. User Clicks House Tour Button
User clicks "House Tours" dropdown in navbar and selects a house guide.

### 2. Tour Manager Initializes
```javascript
tourManager.startTour('shadows')
```

### 3. Intro Sequence
Character appears with intro dialog messages displayed sequentially.

### 4. Step-by-Step Guide
- Character highlights key UI elements
- Dialog bubble provides context
- User can navigate forward/backward
- Step counter shows progress

### 5. Outro Sequence
Character delivers closing messages and tour ends.

## Tour Structure

Each tour in `app/tours.php` follows this structure:

```php
'houseName' => [
    'name' => 'House Name',
    'title' => 'Displayed Title',
    'color' => '#hexcolor',        // Brand color
    'accent' => '#hexcolor',       // Accent color
    'personality' => 'personality_type',
    'iconFolder' => 'FolderName',  // Character images folder
    'intro' => [                   // Array of intro messages
        'Message 1',
        'Message 2',
        'Message 3',
    ],
    'steps' => [                   // Array of tour steps
        [
            'target' => '.selector', // CSS selector to highlight
            'position' => 'bottom',  // Character position
            'title' => 'Step Title',
            'message' => 'Dialog content',
            'dialogStyle' => 'Personality quote',
        ],
        // More steps...
    ],
    'outro' => [                   // Array of outro messages
        'Message 1',
        'Message 2',
        'Message 3',
    ],
],
```

## Character Personalities

### Shadows (Dark, Strategic)
- **Color:** `#2d3748`
- **Personality:** Strategic, thoughtful, quiet, mysterious, logical
- **Speech Style:** Slowly and thoughtfully with deeper insights
- **Icon Folder:** `Shadow_Walking`

### Hipsters (Pink, Creative)
- **Color:** `#d946a6`
- **Personality:** Creative, stylish, expressive, slightly playful
- **Speech Style:** Like a designer or trend-setter
- **Icon Folder:** `Hipster_Walking`

### Engineers (Green, Technical)
- **Color:** `#16a34a`
- **Personality:** Technical, precise, structured, methodical
- **Speech Style:** Clear, pragmatic instructions
- **Icon Folder:** `Engineer_Walking`

### Speedsters (Red, Energetic)
- **Color:** `#dc2626`
- **Personality:** Fast-talking, energetic, motivational
- **Speech Style:** Quick and enthusiastic
- **Icon Folder:** `Speedster_Walking`

## Using the Tour System

### Starting a Tour Programmatically

```javascript
// From any page with tours.js loaded
startTour('shadows');    // Start Shadows tour
startTour('hipsters');   // Start Hipsters tour
startTour('engineers');  // Start Engineers tour
startTour('speedsters'); // Start Speedsters tour
```

### Adding a New House

1. **Add to `app/tours.php`:**
```php
'yourhouse' => [
    'name' => 'Your House',
    'title' => 'Your House Tour',
    'color' => '#yourcolor',
    'accent' => '#youraccentcolor',
    'personality' => 'your_style',
    'iconFolder' => 'YourHouse_Walking',
    'intro' => [ /* ... */ ],
    'steps' => [ /* ... */ ],
    'outro' => [ /* ... */ ],
],
```

2. **Add to `TOUR_CONFIG` in `tours.js`:**
Mirror the PHP configuration in the JavaScript `TOUR_CONFIG` object.

3. **Update navbar dropdown:**
The navbar automatically picks up new tours via:
```php
<?php 
$tours = getAllTours();
foreach ($tours as $tour): 
    // Renders dropdown item
```

4. **Add character images:**
Create folder `/public/assets/images/YourHouse_Walking/` with character frames.

### Customizing Dialog Styles

Edit `public/assets/css/tours.css` to modify:
- `dialog-header` - Title bar color
- `dialog-message` - Message text styling
- `dialog-style` - Personality quote styling
- `tour-character-avatar` - Character image styling
- `tour-highlight` - Element highlight color and animation

## File Locations

```
ChampionTrials2/
├── app/
│   └── tours.php                    # Tour configuration
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── tours.css            # Tour styling
│   │   ├── js/
│   │   │   └── tours.js             # Tour logic
│   │   └── images/
│   │       ├── Shadows_Walking/     # Character frames
│   │       ├── Hipster_Walking/
│   │       ├── Engineer_Walking/
│   │       └── Speedster_Walking/
│   └── index.php                    # Includes tour CSS & JS
└── includes/
    └── navbar.php                   # House Tours dropdown
```

## API Reference

### TourManager Methods

```javascript
// Initialization
tourManager = new TourManager()
tourManager.startTour(houseName)

// Navigation
tourManager.showStep(stepIndex)
tourManager.nextStep()
tourManager.previousStep()

// Lifecycle
tourManager.endTour()

// UI Management
tourManager.createCharacterBubble()
tourManager.createOverlay()
tourManager.highlightElement(selector)
tourManager.animateCharacter(animation)
tourManager.updateDialog(message, title, showButtons, autoNext, callback, stepIndex, totalSteps)

// Properties
tourManager.currentTour           // Current tour data
tourManager.currentStep           // Current step index
tourManager.isActive              // Tour running?
tourManager.characterElement      // DOM reference
tourManager.dialogElement         // DOM reference
tourManager.overlayElement        // DOM reference
```

### Global Functions

```javascript
// Public API
startTour(houseName)               // Start a tour

// Initialization (automatic)
initializeTourSystem()             // Called on DOM ready

// Properties
window.tourManager                 // Global tour manager instance
window.startTour                   // Global tour start function
window.TourManager                 // TourManager class
```

## Styling & Customization

### Colors by House

- **Shadows:** `#2d3748` (dark gray)
- **Hipsters:** `#d946a6` (magenta)
- **Engineers:** `#16a34a` (green)
- **Speedsters:** `#dc2626` (red)

### Dialog Bubble Structure

```
┌─────────────────────────┐
│ Header (Color-coded)    │  <- dialog-header
│ Title + Close Button    │
├─────────────────────────┤
│ Content                 │  <- dialog-content
│ - Message               │
│ - Personality quote     │
├─────────────────────────┤
│ Back | Step X/N | Next  │  <- dialog-footer
└─────────────────────────┘
```

## Animation Details

### Available Animations

1. **slideInUp** - Dialog/character slide in from bottom
2. **fadeOut** - Character disappears
3. **characterBounce** - Character entry bounce
4. **dialogBounce** - Dialog appearance bounce
5. **tourPulse** - Highlight element pulsing effect

### Timing

- Intro messages: Auto-advance every 3 seconds
- Step dialogs: Manual control
- Outro messages: Auto-advance every 3 seconds
- Animations: 0.3-0.4s duration

## Responsive Behavior

The tour system is fully responsive:
- **Desktop:** Large character (120px), wide dialog (400px)
- **Tablet:** Medium character (100px), medium dialog
- **Mobile:** Small character (80px), narrow dialog (90vw)

Character positions adjust based on viewport and target element location.

## Accessibility

- Close button available on all dialogs
- ESC key to close (can be added)
- Step counter for context
- Clear navigation buttons
- High contrast text

## Troubleshooting

### Tour Not Starting
1. Check browser console for errors
2. Verify `tours.js` is loaded: check Network tab
3. Verify navbar dropdown is visible
4. Check that tour house name matches configuration

### Character Images Not Showing
1. Verify image folder path: `/ChampionTrials2/public/assets/images/HouseName_Walking/`
2. Check image file naming convention
3. Verify BASE_PATH configuration if using subdirectory
4. Check browser DevTools for 404 errors

### Dialog Styling Issues
1. Verify `tours.css` is linked: check `<head>` in page source
2. Clear browser cache
3. Check CSS specificity conflicts with existing styles
4. Verify color values in tour configuration

## Future Enhancements

Possible extensions:
- [ ] Tutorial video integration
- [ ] User progress tracking (localStorage)
- [ ] Guided task completion (checkpoints)
- [ ] Keyboard navigation (arrow keys, ESC)
- [ ] Mobile touch gestures
- [ ] Multi-language support
- [ ] Tour analytics/tracking
- [ ] Custom animation sequences per house
- [ ] Interactive quizzes after tours
- [ ] House-specific features/tools unlock

## Code Examples

### Start Tour from External Button
```html
<button onclick="startTour('engineers')">
    Take Engineers Tour
</button>
```

### Custom Tour Trigger
```javascript
document.getElementById('tour-button').addEventListener('click', () => {
    startTour('hipsters');
});
```

### Check If Tour is Active
```javascript
if (tourManager && tourManager.isActive) {
    console.log('Tour is running');
}
```

### Stop Current Tour
```javascript
if (tourManager && tourManager.isActive) {
    tourManager.endTour();
}
```

## Performance Notes

- Tour system is lightweight (~15KB JS + ~8KB CSS)
- No external dependencies required
- Works with Bootstrap 5+ navbar
- Minimal DOM manipulation
- CSS animations use GPU acceleration
- Image preloading recommended for large character sprite sheets

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Limited support (CSS animations may not work)

---

**Last Updated:** November 2024
**Version:** 1.0
**Status:** Production Ready
