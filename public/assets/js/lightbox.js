/**
 * CANDIDv2 Lightbox
 *
 * A vanilla JS lightbox with carousel navigation for image galleries.
 * Displays image metadata and edit links when user has permissions.
 */
(function() {
    'use strict';

    // State
    let images = [];
    let currentIndex = 0;
    let lightbox = null;
    let isOpen = false;

    // DOM Elements (created on init)
    let overlay, container, img, prevBtn, nextBtn, closeBtn;
    let caption, counter, metadata, actions;

    /**
     * Initialize the lightbox
     */
    function init() {
        createLightboxDOM();
        bindEvents();
    }

    /**
     * Create the lightbox DOM structure
     */
    function createLightboxDOM() {
        overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.innerHTML = `
            <div class="lightbox-container">
                <button class="lightbox-close" aria-label="Close">&times;</button>
                <button class="lightbox-prev" aria-label="Previous">&lsaquo;</button>
                <button class="lightbox-next" aria-label="Next">&rsaquo;</button>
                <div class="lightbox-content">
                    <img class="lightbox-image" src="" alt="">
                </div>
                <div class="lightbox-footer">
                    <div class="lightbox-info">
                        <div class="lightbox-caption"></div>
                        <div class="lightbox-metadata"></div>
                    </div>
                    <div class="lightbox-actions">
                        <a href="#" class="lightbox-action lightbox-details" title="View details">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <span>Details</span>
                        </a>
                        <a href="#" class="lightbox-action lightbox-edit" title="Edit image" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                <path d="m15 5 4 4"/>
                            </svg>
                            <span>Edit</span>
                        </a>
                        <span class="lightbox-counter"></span>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Cache element references
        container = overlay.querySelector('.lightbox-container');
        img = overlay.querySelector('.lightbox-image');
        prevBtn = overlay.querySelector('.lightbox-prev');
        nextBtn = overlay.querySelector('.lightbox-next');
        closeBtn = overlay.querySelector('.lightbox-close');
        caption = overlay.querySelector('.lightbox-caption');
        metadata = overlay.querySelector('.lightbox-metadata');
        actions = overlay.querySelector('.lightbox-actions');
        counter = overlay.querySelector('.lightbox-counter');
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Close button
        closeBtn.addEventListener('click', close);

        // Navigation buttons
        prevBtn.addEventListener('click', showPrev);
        nextBtn.addEventListener('click', showNext);

        // Click outside to close
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                close();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', handleKeydown);

        // Delegate click events for image cards
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.image-card a');
            if (link && link.closest('.image-grid')) {
                e.preventDefault();
                openFromGrid(link);
            }
        });

        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        overlay.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        overlay.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const diff = touchStartX - touchEndX;
            const threshold = 50;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    showNext();
                } else {
                    showPrev();
                }
            }
        }
    }

    /**
     * Handle keyboard events
     */
    function handleKeydown(e) {
        if (!isOpen) return;

        switch (e.key) {
            case 'Escape':
                close();
                break;
            case 'ArrowLeft':
                showPrev();
                break;
            case 'ArrowRight':
                showNext();
                break;
        }
    }

    /**
     * Open lightbox from an image grid
     */
    function openFromGrid(clickedLink) {
        const grid = clickedLink.closest('.image-grid');
        const cards = grid.querySelectorAll('.image-card');

        // Build image list from the grid
        images = [];
        cards.forEach(function(card, index) {
            const link = card.querySelector('a');
            const thumbnail = card.querySelector('img');
            const captionEl = card.querySelector('.caption');

            if (link && thumbnail) {
                const imageId = link.href.match(/\/image\/(\d+)/)?.[1];
                images.push({
                    id: imageId,
                    src: '/image/' + imageId + '/show',
                    thumb: thumbnail.src,
                    alt: thumbnail.alt || '',
                    caption: captionEl ? captionEl.textContent.trim() : '',
                    href: link.href,
                    dateTaken: card.dataset.dateTaken || '',
                    photographer: card.dataset.photographer || '',
                    camera: card.dataset.camera || '',
                    canEdit: card.dataset.canEdit === '1'
                });

                // Find which image was clicked
                if (link === clickedLink) {
                    currentIndex = index;
                }
            }
        });

        if (images.length > 0) {
            open(currentIndex);
        }
    }

    /**
     * Open the lightbox at a specific index
     */
    function open(index) {
        currentIndex = index;
        isOpen = true;

        // Show overlay
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Load and display image
        showImage(currentIndex);

        // Update URL hash
        updateHash();

        // Preload adjacent images
        preloadAdjacent();
    }

    /**
     * Close the lightbox
     */
    function close() {
        isOpen = false;
        overlay.classList.remove('active');
        document.body.style.overflow = '';

        // Clear hash
        if (window.location.hash.startsWith('#image-')) {
            history.pushState('', document.title, window.location.pathname + window.location.search);
        }
    }

    /**
     * Format a date string for display
     */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * Show the image at the given index
     */
    function showImage(index) {
        const image = images[index];
        if (!image) return;

        // Add loading state
        img.classList.add('loading');

        // Load full image
        const newImg = new Image();
        newImg.onload = function() {
            img.src = image.src;
            img.alt = image.alt;
            img.classList.remove('loading');
        };
        newImg.onerror = function() {
            img.src = image.thumb; // Fallback to thumbnail
            img.alt = image.alt;
            img.classList.remove('loading');
        };
        newImg.src = image.src;

        // Update caption
        caption.textContent = image.caption || image.alt || 'Untitled';

        // Update metadata
        const metaParts = [];
        if (image.dateTaken) {
            metaParts.push(formatDate(image.dateTaken));
        }
        if (image.photographer) {
            metaParts.push('by ' + image.photographer);
        }
        if (image.camera) {
            metaParts.push(image.camera);
        }
        metadata.textContent = metaParts.join(' \u2022 ');

        // Update action links
        const detailsLink = actions.querySelector('.lightbox-details');
        const editLink = actions.querySelector('.lightbox-edit');

        detailsLink.href = '/image/' + image.id;
        editLink.href = '/image/' + image.id + '/edit';
        editLink.style.display = image.canEdit ? 'inline-flex' : 'none';

        // Update counter
        counter.textContent = (index + 1) + ' / ' + images.length;

        // Update navigation visibility
        prevBtn.style.visibility = index > 0 ? 'visible' : 'hidden';
        nextBtn.style.visibility = index < images.length - 1 ? 'visible' : 'hidden';

        // Update hash
        updateHash();
    }

    /**
     * Show previous image
     */
    function showPrev() {
        if (currentIndex > 0) {
            currentIndex--;
            showImage(currentIndex);
            preloadAdjacent();
        }
    }

    /**
     * Show next image
     */
    function showNext() {
        if (currentIndex < images.length - 1) {
            currentIndex++;
            showImage(currentIndex);
            preloadAdjacent();
        }
    }

    /**
     * Preload adjacent images for smoother navigation
     */
    function preloadAdjacent() {
        [-1, 1].forEach(function(offset) {
            const idx = currentIndex + offset;
            if (idx >= 0 && idx < images.length) {
                const preload = new Image();
                preload.src = images[idx].src;
            }
        });
    }

    /**
     * Update URL hash with current image
     */
    function updateHash() {
        const image = images[currentIndex];
        if (image && image.id) {
            history.replaceState(null, '', '#image-' + image.id);
        }
    }

    /**
     * Check URL hash on page load
     */
    function checkHash() {
        const hash = window.location.hash;
        if (hash.startsWith('#image-')) {
            const imageId = hash.replace('#image-', '');

            // Find the image in any grid on the page
            const link = document.querySelector('.image-card a[href$="/image/' + imageId + '"]');
            if (link) {
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    openFromGrid(link);
                }, 100);
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
            checkHash();
        });
    } else {
        init();
        checkHash();
    }

    // Handle browser back/forward
    window.addEventListener('hashchange', function() {
        if (!window.location.hash.startsWith('#image-') && isOpen) {
            close();
        }
    });

})();
