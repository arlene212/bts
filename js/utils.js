// Utility functions for the BTS system

// Loading indicator management
function showLoading(message = 'Loading...') {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-indicator';
    loadingDiv.innerHTML = `
        <div class="loading-spinner"></div>
        <p>${message}</p>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('loading-indicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// API error handling
function handleApiError(response) {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
}

// Safe DOM access
function safeQuerySelector(selector, context = document) {
    try {
        return context.querySelector(selector);
    } catch (error) {
        console.error(`Error finding element with selector "${selector}":`, error);
        return null;
    }
}

// Safe event handler
function addSafeEventListener(element, eventType, handler) {
    if (!element) {
        console.error('Cannot add event listener to null element');
        return;
    }
    
    element.addEventListener(eventType, (...args) => {
        try {
            handler(...args);
        } catch (error) {
            console.error('Error in event handler:', error);
            alert('An error occurred. Please try again.');
        }
    });
}

// Form validation
function validateForm(form, requiredFields) {
    if (!form) {
        console.error('Form is missing');
        return false;
    }

    let isValid = true;
    const errors = [];

    requiredFields.forEach(field => {
        const element = form.elements[field];
        if (!element || !element.value.trim()) {
            errors.push(`${field} is required`);
            isValid = false;
        }
    });

    if (!isValid) {
        alert('Please fill in all required fields: \n' + errors.join('\n'));
    }

    return isValid;
}

// Safe JSON parsing
function safeJsonParse(str, fallback = null) {
    try {
        return JSON.parse(str);
    } catch (error) {
        console.error('Error parsing JSON:', error);
        return fallback;
    }
}

// Safe attribute getter
function safeGetAttribute(element, attribute, fallback = '') {
    if (!element) {
        console.error(`Element is null, cannot get attribute "${attribute}"`);
        return fallback;
    }
    
    try {
        const value = element.getAttribute(attribute);
        return value !== null ? value : fallback;
    } catch (error) {
        console.error(`Error getting attribute "${attribute}":`, error);
        return fallback;
    }
}

// Safe modal operations
function safeOpenModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(`Modal with id "${modalId}" not found`);
        return;
    }

    try {
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error(`Error opening modal "${modalId}":`, error);
    }
}

function safeCloseModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(`Modal with id "${modalId}" not found`);
        return;
    }

    try {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        
        // Reset form if exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    } catch (error) {
        console.error(`Error closing modal "${modalId}":`, error);
    }
}

// Export for use in other files
window.BtsUtils = {
    showLoading,
    hideLoading,
    handleApiError,
    safeQuerySelector,
    addSafeEventListener,
    validateForm,
    safeJsonParse,
    safeGetAttribute,
    safeOpenModal,
    safeCloseModal
};