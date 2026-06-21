// Toggles the dropdown open/closed and flips the arrow icon accordingly.
function toggleDropdown(dropdownId, event) {
    const dropdown = document.getElementById(dropdownId);
    dropdown.classList.toggle("hidden");
    if (event) {
        event.stopPropagation();
    }
    const arrowDown = document.querySelector('.' + dropdownId.replace('Dropdown', 'ArrowDown'));
    const arrowUp = document.querySelector('.' + dropdownId.replace('Dropdown', 'ArrowUp'));

    if (dropdown.classList.contains('hidden')) {
        arrowDown.classList.remove('hidden');
        arrowUp.classList.add('hidden');
    } else {
        arrowDown.classList.add('hidden');
        arrowUp.classList.remove('hidden');
    }
}

// Closes all open dropdowns and resets arrows when clicking outside a trigger.
window.onclick = function(event) {
    if (!event.target.matches('.cursor-pointer')) {
        const dropdowns = document.querySelectorAll('.absolute');
        dropdowns.forEach(dropdown => {
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                const arrowDown = document.querySelector('.' + dropdown.id.replace('Dropdown', 'ArrowDown'));
                const arrowUp = document.querySelector('.' + dropdown.id.replace('Dropdown', 'ArrowUp'));
                arrowDown.classList.remove('hidden');
                arrowUp.classList.add('hidden');
            }
        });
    }
}

// Sets the button label to the chosen option text and closes its dropdown.
function selectOption(buttonId, optionText) {
    document.querySelector('.' + buttonId).textContent = optionText;
    toggleDropdown(buttonId.replace('Button', 'Dropdown'));
}
