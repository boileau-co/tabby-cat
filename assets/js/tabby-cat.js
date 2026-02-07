/**
 * Tabby Cat - Front-end JavaScript
 * Handles category switching, item selection, gallery navigation, and mobile layout
 */

(function() {
    'use strict';

    const MOBILE_BREAKPOINT = 769;
    let isMobile = window.innerWidth <= MOBILE_BREAKPOINT;

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const containers = document.querySelectorAll('.tabby-cat');
        containers.forEach(initTabbyCat);
        
        // Handle resize
        window.addEventListener('resize', debounce(() => {
            const wasMobile = isMobile;
            isMobile = window.innerWidth <= MOBILE_BREAKPOINT;
            
            if (wasMobile !== isMobile) {
                containers.forEach(container => {
                    if (isMobile) {
                        initMobileLayout(container);
                    } else {
                        destroyMobileLayout(container);
                        // Re-init desktop behavior
                        initDesktopDefaults(container);
                    }
                });
            }
        }, 150));
    }

    function initTabbyCat(container) {
        initCategoryTabs(container);
        initItemTabs(container);
        initGalleries(container);
        initVideoThumbnails(container);
        initLightbox(container);
        
        if (isMobile) {
            initMobileLayout(container);
        } else {
            initDesktopDefaults(container);
        }
    }

    /**
     * Desktop: Show first item's content by default
     */
    function initDesktopDefaults(container) {
        const firstPanel = container.querySelector('.tabby-cat__panel.is-active');
        if (firstPanel) {
            const firstDetail = firstPanel.querySelector('.tabby-cat__item-detail');
            if (firstDetail) {
                firstDetail.classList.add('is-active');
                firstDetail.hidden = false;
            }
        }
    }

    /**
     * Mobile Layout Initialization
     */
    function initMobileLayout(container) {
        container.classList.add('is-mobile');
        
        // Create mobile nav structure if it doesn't exist
        if (!container.querySelector('.tabby-cat__mobile-nav')) {
            createMobileNav(container);
        }
        
        // Hide all item details initially on mobile
        container.querySelectorAll('.tabby-cat__item-detail').forEach(detail => {
            detail.classList.remove('is-mobile-visible');
        });

        // Show first item's content by default
        const firstMobileCategoryTab = container.querySelector('.tabby-cat__mobile-category-tab.is-active');
        if (firstMobileCategoryTab) {
            const categoryId = firstMobileCategoryTab.dataset.category;
            const firstMobileItemTab = container.querySelector('.tabby-cat__mobile-items .tabby-cat__mobile-item-tab');
            if (firstMobileItemTab) {
                const itemId = firstMobileItemTab.dataset.item;
                firstMobileItemTab.classList.add('is-active');
                showMobileContent(container, categoryId, itemId, true);
            }
        }
    }

    /**
     * Create Mobile Navigation Structure
     */
    function createMobileNav(container) {
        const mobileNav = document.createElement('div');
        mobileNav.className = 'tabby-cat__mobile-nav';
        
        // Categories column
        const categoriesCol = document.createElement('div');
        categoriesCol.className = 'tabby-cat__mobile-categories';
        
        // Items column
        const itemsCol = document.createElement('div');
        itemsCol.className = 'tabby-cat__mobile-items';
        
        // Get all categories
        const categoryTabs = container.querySelectorAll('.tabby-cat__category-tab');
        const panels = container.querySelectorAll('.tabby-cat__panel');
        
        categoryTabs.forEach((tab, index) => {
            const categoryId = tab.dataset.category;
            const categoryName = tab.textContent.trim().replace(/\d+$/, '').trim(); // Remove count number
            
            const mobileCategory = document.createElement('button');
            mobileCategory.className = 'tabby-cat__mobile-category-tab' + (index === 0 ? ' is-active' : '');
            mobileCategory.textContent = categoryName;
            mobileCategory.dataset.category = categoryId;
            
            mobileCategory.addEventListener('click', () => {
                // Update active category
                categoriesCol.querySelectorAll('.tabby-cat__mobile-category-tab').forEach(c => {
                    c.classList.remove('is-active');
                });
                mobileCategory.classList.add('is-active');
                
                // Update items column
                updateMobileItems(container, itemsCol, categoryId);
                
                // Update original panels (for data consistency)
                panels.forEach(panel => {
                    const isTarget = panel.id === `tabby-panel-${categoryId}`;
                    panel.classList.toggle('is-active', isTarget);
                });
            });
            
            categoriesCol.appendChild(mobileCategory);
        });
        
        mobileNav.appendChild(categoriesCol);
        mobileNav.appendChild(itemsCol);
        
        // Create horizontal divider between content and nav
        const mobileDivider = document.createElement('hr');
        mobileDivider.className = 'tabby-cat__mobile-divider';

        // Insert mobile divider and nav after the desktop divider (or at end)
        const divider = container.querySelector('.tabby-cat__divider');
        if (divider) {
            divider.after(mobileDivider);
            mobileDivider.after(mobileNav);
        } else {
            container.appendChild(mobileDivider);
            container.appendChild(mobileNav);
        }
        
        // Initialize with first category's items
        const firstCategoryId = categoryTabs[0]?.dataset.category;
        if (firstCategoryId) {
            updateMobileItems(container, itemsCol, firstCategoryId);
        }
    }

    /**
     * Update Mobile Items Column
     */
    function updateMobileItems(container, itemsCol, categoryId) {
        itemsCol.innerHTML = '';
        
        const panel = container.querySelector(`#tabby-panel-${categoryId}`);
        if (!panel) return;
        
        const itemTabs = panel.querySelectorAll('.tabby-cat__item-tab');
        
        itemTabs.forEach(tab => {
            const itemId = tab.dataset.item;
            const itemName = tab.textContent.trim();
            
            const mobileItem = document.createElement('button');
            mobileItem.className = 'tabby-cat__mobile-item-tab';
            mobileItem.textContent = itemName;
            mobileItem.dataset.item = itemId;
            mobileItem.dataset.panel = categoryId;
            
            // Check if this item is currently showing content
            const detail = panel.querySelector(`#tabby-item-${itemId}`);
            if (detail && detail.classList.contains('is-mobile-visible')) {
                mobileItem.classList.add('is-active');
            }
            
            mobileItem.addEventListener('click', () => {
                // Update active state on mobile items
                itemsCol.querySelectorAll('.tabby-cat__mobile-item-tab').forEach(i => {
                    i.classList.remove('is-active');
                });
                mobileItem.classList.add('is-active');
                
                // Show this item's content
                showMobileContent(container, categoryId, itemId);
            });
            
            itemsCol.appendChild(mobileItem);
        });
    }

    /**
     * Show Content on Mobile
     */
    function showMobileContent(container, categoryId, itemId, skipScroll) {
        // Get the mobile content container (or create it)
        let mobileContent = container.querySelector('.tabby-cat__mobile-content');
        
        if (!mobileContent) {
            mobileContent = document.createElement('div');
            mobileContent.className = 'tabby-cat__mobile-content';
            
            // Insert at the top of the container (after any existing title outside)
            const firstChild = container.firstElementChild;
            container.insertBefore(mobileContent, firstChild);
        }
        
        // Get the content from the original item detail
        const panel = container.querySelector(`#tabby-panel-${categoryId}`);
        const detail = panel?.querySelector(`#tabby-item-${itemId}`);
        
        if (detail) {
            // Clone the content
            const contentClone = detail.querySelector('.tabby-cat__detail-content');
            if (contentClone) {
                mobileContent.innerHTML = '';
                mobileContent.appendChild(contentClone.cloneNode(true));
                mobileContent.classList.add('is-visible');
                
                // Re-init video thumbnails in the cloned content
                initVideoThumbnails(mobileContent);
                
                // Mark original as mobile-visible for tracking
                container.querySelectorAll('.tabby-cat__item-detail').forEach(d => {
                    d.classList.remove('is-mobile-visible');
                });
                detail.classList.add('is-mobile-visible');
                
                // Scroll to top of content smoothly (skip on initial load)
                if (!skipScroll) {
                    mobileContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    }

    /**
     * Destroy Mobile Layout
     */
    function destroyMobileLayout(container) {
        container.classList.remove('is-mobile');
        
        // Remove mobile nav
        const mobileNav = container.querySelector('.tabby-cat__mobile-nav');
        if (mobileNav) {
            mobileNav.remove();
        }

        // Remove mobile divider
        const mobileDivider = container.querySelector('.tabby-cat__mobile-divider');
        if (mobileDivider) {
            mobileDivider.remove();
        }

        // Remove mobile content
        const mobileContent = container.querySelector('.tabby-cat__mobile-content');
        if (mobileContent) {
            mobileContent.remove();
        }
        
        // Reset mobile-visible classes
        container.querySelectorAll('.tabby-cat__item-detail').forEach(d => {
            d.classList.remove('is-mobile-visible');
        });
    }

    /**
     * Category Tabs (Top Level) - Desktop
     */
    function initCategoryTabs(container) {
        const categoryTabs = container.querySelectorAll('.tabby-cat__category-tab');
        const categoryPanels = container.querySelectorAll('.tabby-cat__panel');

        categoryTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                if (isMobile) return; // Mobile uses different navigation
                
                const categoryId = tab.dataset.category;

                // Update tabs
                categoryTabs.forEach(t => {
                    t.classList.remove('is-active');
                    t.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');

                // Update panels
                categoryPanels.forEach(panel => {
                    const isTarget = panel.id === `tabby-panel-${categoryId}`;
                    panel.classList.toggle('is-active', isTarget);
                    panel.hidden = !isTarget;
                });
                
                // Show first item in new category
                const activePanel = container.querySelector('.tabby-cat__panel.is-active');
                if (activePanel) {
                    const firstItemTab = activePanel.querySelector('.tabby-cat__item-tab');
                    const firstDetail = activePanel.querySelector('.tabby-cat__item-detail');
                    
                    activePanel.querySelectorAll('.tabby-cat__item-tab').forEach(t => {
                        t.classList.remove('is-active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    activePanel.querySelectorAll('.tabby-cat__item-detail').forEach(d => {
                        d.classList.remove('is-active');
                        d.hidden = true;
                    });
                    
                    if (firstItemTab) {
                        firstItemTab.classList.add('is-active');
                        firstItemTab.setAttribute('aria-selected', 'true');
                    }
                    if (firstDetail) {
                        firstDetail.classList.add('is-active');
                        firstDetail.hidden = false;
                    }
                }
            });

            // Keyboard navigation
            tab.addEventListener('keydown', (e) => {
                if (!isMobile) {
                    handleTabKeydown(e, categoryTabs);
                }
            });
        });
    }

    /**
     * Item Tabs (Second Level) - Desktop
     */
    function initItemTabs(container) {
        const panels = container.querySelectorAll('.tabby-cat__panel');

        panels.forEach(panel => {
            const itemTabs = panel.querySelectorAll('.tabby-cat__item-tab');
            const itemDetails = panel.querySelectorAll('.tabby-cat__item-detail');

            itemTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    if (isMobile) return; // Mobile uses different navigation
                    
                    const itemId = tab.dataset.item;

                    // Update tabs
                    itemTabs.forEach(t => {
                        t.classList.remove('is-active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    tab.classList.add('is-active');
                    tab.setAttribute('aria-selected', 'true');

                    // Update detail panels
                    itemDetails.forEach(detail => {
                        const isTarget = detail.id === `tabby-item-${itemId}`;
                        detail.classList.toggle('is-active', isTarget);
                        detail.hidden = !isTarget;
                    });
                });

                // Keyboard navigation
                tab.addEventListener('keydown', (e) => {
                    if (!isMobile) {
                        handleTabKeydown(e, itemTabs, true);
                    }
                });
            });
        });
    }

    /**
     * Gallery Navigation
     */
    function initGalleries(container) {
        const galleries = container.querySelectorAll('.tabby-cat__gallery');

        galleries.forEach(gallery => {
            // Skip if already initialized
            if (gallery.dataset.initialized) return;
            gallery.dataset.initialized = 'true';
            
            const images = gallery.querySelectorAll('.tabby-cat__gallery-image');
            const prevBtn = gallery.querySelector('.tabby-cat__gallery-prev');
            const nextBtn = gallery.querySelector('.tabby-cat__gallery-next');
            const currentSpan = gallery.querySelector('.tabby-cat__gallery-counter .current');
            
            if (images.length < 2) return;

            let currentIndex = 0;

            // Initialize first image as active
            images[0].classList.add('is-active');

            function showImage(index) {
                // Wrap around
                if (index < 0) index = images.length - 1;
                if (index >= images.length) index = 0;

                images.forEach((img, i) => {
                    img.classList.toggle('is-active', i === index);
                    img.style.display = i === index ? 'block' : 'none';
                });

                currentIndex = index;
                if (currentSpan) {
                    currentSpan.textContent = currentIndex + 1;
                }
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    showImage(currentIndex - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    showImage(currentIndex + 1);
                });
            }

            // Keyboard support for gallery
            gallery.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    showImage(currentIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    showImage(currentIndex + 1);
                }
            });

            // Initialize display
            showImage(0);
        });
    }

    /**
     * Keyboard Navigation Helper
     */
    function handleTabKeydown(e, tabs, isVertical = false) {
        const currentIndex = Array.from(tabs).indexOf(e.target);
        let newIndex;

        const prevKey = isVertical ? 'ArrowUp' : 'ArrowLeft';
        const nextKey = isVertical ? 'ArrowDown' : 'ArrowRight';

        switch (e.key) {
            case prevKey:
                e.preventDefault();
                newIndex = currentIndex - 1;
                if (newIndex < 0) newIndex = tabs.length - 1;
                tabs[newIndex].focus();
                tabs[newIndex].click();
                break;

            case nextKey:
                e.preventDefault();
                newIndex = currentIndex + 1;
                if (newIndex >= tabs.length) newIndex = 0;
                tabs[newIndex].focus();
                tabs[newIndex].click();
                break;

            case 'Home':
                e.preventDefault();
                tabs[0].focus();
                tabs[0].click();
                break;

            case 'End':
                e.preventDefault();
                tabs[tabs.length - 1].focus();
                tabs[tabs.length - 1].click();
                break;
        }
    }

    /**
     * Video Thumbnail Click Handler
     */
    function initVideoThumbnails(container) {
        const thumbnails = container.querySelectorAll('.tabby-cat__video-thumbnail');

        thumbnails.forEach(thumbnail => {
            // Skip if already initialized
            if (thumbnail.dataset.initialized) return;
            thumbnail.dataset.initialized = 'true';
            
            const playBtn = thumbnail.querySelector('.tabby-cat__video-play');
            
            function loadVideo() {
                const videoData = thumbnail.dataset.video;
                if (videoData) {
                    const videoEmbed = atob(videoData);
                    const videoContainer = thumbnail.parentElement;
                    thumbnail.remove();
                    videoContainer.innerHTML = videoEmbed;

                    // Autoplay the video
                    const iframe = videoContainer.querySelector('iframe');
                    if (iframe) {
                        const src = iframe.getAttribute('src');
                        if (src) {
                            const separator = src.includes('?') ? '&' : '?';
                            iframe.setAttribute('src', src + separator + 'autoplay=1');
                        }
                        const allow = iframe.getAttribute('allow') || '';
                        if (!allow.includes('autoplay')) {
                            iframe.setAttribute('allow', allow ? allow + '; autoplay' : 'autoplay');
                        }
                    }
                }
            }

            thumbnail.addEventListener('click', loadVideo);
            
            if (playBtn) {
                playBtn.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        loadVideo();
                    }
                });
            }
        });
    }

    /**
     * Lightbox for Images and Gallery Images
     */
    function initLightbox(container) {
        let lightbox = null;
        let lightboxImg = null;
        let lightboxPrev = null;
        let lightboxNext = null;
        let lightboxCounter = null;
        let galleryImages = [];
        let currentGalleryIndex = 0;

        function createLightbox() {
            lightbox = document.createElement('div');
            lightbox.className = 'tabby-cat__lightbox';
            lightbox.innerHTML =
                '<button class="tabby-cat__lightbox-close" aria-label="Close lightbox">&times;</button>' +
                '<button class="tabby-cat__lightbox-prev" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>' +
                '<img src="" alt="">' +
                '<button class="tabby-cat__lightbox-next" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>' +
                '<span class="tabby-cat__lightbox-counter"></span>';
            document.body.appendChild(lightbox);

            lightboxImg = lightbox.querySelector('img');
            lightboxPrev = lightbox.querySelector('.tabby-cat__lightbox-prev');
            lightboxNext = lightbox.querySelector('.tabby-cat__lightbox-next');
            lightboxCounter = lightbox.querySelector('.tabby-cat__lightbox-counter');
            var closeBtn = lightbox.querySelector('.tabby-cat__lightbox-close');

            closeBtn.addEventListener('click', closeLightbox);
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) closeLightbox();
            });
            lightboxPrev.addEventListener('click', function() {
                showGalleryImage(currentGalleryIndex - 1);
            });
            lightboxNext.addEventListener('click', function() {
                showGalleryImage(currentGalleryIndex + 1);
            });

            document.addEventListener('keydown', function(e) {
                if (!lightbox || !lightbox.classList.contains('is-visible')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft' && galleryImages.length > 1) showGalleryImage(currentGalleryIndex - 1);
                if (e.key === 'ArrowRight' && galleryImages.length > 1) showGalleryImage(currentGalleryIndex + 1);
            });
        }

        function openLightbox(src, alt, gallery, startIndex) {
            if (!lightbox) createLightbox();

            galleryImages = gallery || [];
            currentGalleryIndex = startIndex || 0;

            if (galleryImages.length > 1) {
                lightbox.classList.add('is-gallery');
                showGalleryImage(currentGalleryIndex);
            } else {
                lightbox.classList.remove('is-gallery');
                lightboxImg.src = src;
                lightboxImg.alt = alt || '';
            }

            // Force reflow then add visible class for transition
            lightbox.offsetHeight;
            lightbox.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            if (!lightbox) return;
            lightbox.classList.remove('is-visible');
            document.body.style.overflow = '';
            setTimeout(function() {
                if (lightboxImg) lightboxImg.src = '';
            }, 300);
        }

        function showGalleryImage(index) {
            if (index < 0) index = galleryImages.length - 1;
            if (index >= galleryImages.length) index = 0;
            currentGalleryIndex = index;
            lightboxImg.src = galleryImages[index].src;
            lightboxImg.alt = galleryImages[index].alt || '';
            lightboxCounter.textContent = (index + 1) + ' / ' + galleryImages.length;
        }

        // Event delegation for image clicks
        container.addEventListener('click', function(e) {
            var singleImage = e.target.closest('.tabby-cat__image[data-full-src]');
            if (singleImage) {
                e.preventDefault();
                openLightbox(singleImage.dataset.fullSrc, singleImage.alt);
                return;
            }

            var galleryImage = e.target.closest('.tabby-cat__gallery-image[data-full-src]');
            if (galleryImage) {
                e.preventDefault();
                var galleryTrack = galleryImage.closest('.tabby-cat__gallery-track');
                if (galleryTrack) {
                    var allImages = galleryTrack.querySelectorAll('.tabby-cat__gallery-image[data-full-src]');
                    var gallery = Array.from(allImages).map(function(img) {
                        return { src: img.dataset.fullSrc, alt: img.alt };
                    });
                    var clickedIndex = Array.from(allImages).indexOf(galleryImage);
                    openLightbox(null, null, gallery, clickedIndex);
                }
                return;
            }
        });
    }

    /**
     * Debounce Helper
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})();
