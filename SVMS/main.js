// Notification system
document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    updateNotificationCount();
    
    // Auto-refresh notifications every 30 seconds
    setInterval(() => {
        loadNotifications();
        updateNotificationCount();
    }, 30000);
    
    // Load notifications when dropdown is opened
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationMenu = document.getElementById('notification-dropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('show.bs.dropdown', (e) => {
            positionNotificationDropdown();
            loadNotifications();
        });
        // Ensure dropdown repositions on hide/resize/scroll
        notificationDropdown.addEventListener('hide.bs.dropdown', () => {
            resetNotificationDropdownPosition();
        });
        window.addEventListener('resize', () => {
            if (document.querySelector('#notification-dropdown.show')) positionNotificationDropdown();
        });
        window.addEventListener('scroll', () => {
            if (document.querySelector('#notification-dropdown.show')) positionNotificationDropdown();
        });
        notificationDropdown.addEventListener('click', () => {
            setTimeout(() => {
                loadNotifications();
            }, 100);
        });
    }

    // Position the notification dropdown to avoid clipping inside overflowed parents
    function positionNotificationDropdown() {
        const btn = document.getElementById('notificationDropdown');
        const menu = document.getElementById('notification-dropdown');
        if (!btn || !menu) return;

        // Float the notification panel below the navbar at the top-right corner
        const navbar = document.querySelector('.navbar');
        let topOffset = 64; // fallback
        if (navbar) {
            const nrect = navbar.getBoundingClientRect();
            topOffset = Math.max(48, nrect.bottom + 8);
        }
        menu.style.position = 'fixed';
        menu.style.top = topOffset + 'px';
        menu.style.right = '12px';
        menu.style.left = 'auto';
        menu.style.zIndex = 4000;
        menu.style.minWidth = '360px';
        menu.style.maxHeight = '60vh';
        menu.style.overflowY = 'auto';
    }

    function resetNotificationDropdownPosition() {
        const menu = document.getElementById('notification-dropdown');
        if (!menu) return;
        menu.style.position = '';
        menu.style.top = '';
        menu.style.right = '';
        menu.style.left = '';
        menu.style.zIndex = '';
        menu.style.minWidth = '';
        menu.style.maxHeight = '';
        menu.style.overflowY = '';
    }
});

function loadNotifications() {
    const notificationList = document.getElementById('notification-list');
    if (!notificationList) return;
    
    // Show loading state
    notificationList.innerHTML = `
        <li>
            <div class="dropdown-item text-center text-muted py-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </li>
    `;
    
    fetch('api_notifications.php?action=list&limit=10')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications data:', data); // Debug log
            if (data.success && data.notifications && Array.isArray(data.notifications) && data.notifications.length > 0) {
                notificationList.innerHTML = data.notifications.map(notif => {
                    const isUnread = !notif.is_read;
                    const typeClass = notif.type === 'danger' ? 'danger' : 
                                    notif.type === 'warning' ? 'warning' : 
                                    notif.type === 'success' ? 'success' : 'info';
                    const iconClass = notif.type === 'danger' ? 'bi-exclamation-triangle' : 
                                     notif.type === 'warning' ? 'bi-exclamation-circle' : 
                                     notif.type === 'success' ? 'bi-check-circle' : 'bi-info-circle';
                    const linkUrl = notif.link && notif.link !== 'null' && notif.link !== '' ? notif.link : 'notifications.php';
                    
                    return `
                        <li>
                            <a class="dropdown-item notification-item ${isUnread ? 'unread' : ''} ${notif.type}" 
                               href="${linkUrl}" 
                               ${isUnread ? `onclick="event.preventDefault(); markAsRead(${notif.id}); window.location.href='${linkUrl}';"` : ''}>
                                <div class="d-flex align-items-start">
                                    <div class="me-2 mt-1">
                                        <i class="bi ${iconClass} text-${typeClass} fs-6"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="fw-${isUnread ? 'bold' : 'semibold'} text-truncate" style="max-width: 250px;">${escapeHtml(notif.title)}</div>
                                            ${isUnread ? '<span class="badge bg-danger rounded-pill ms-2" style="font-size: 0.6rem; flex-shrink: 0;">New</span>' : ''}
                                        </div>
                                        <div class="text-muted small mb-1" style="line-height: 1.4; word-wrap: break-word;">
                                            ${escapeHtml(notif.message.length > 80 ? notif.message.substring(0, 80) + '...' : notif.message)}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock"></i> ${notif.time_ago}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }).join('');
            } else {
                notificationList.innerHTML = `
                    <li>
                        <div class="dropdown-item text-center text-muted py-4">
                            <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                            <small>No notifications</small>
                        </div>
                    </li>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationList.innerHTML = `
                <li>
                    <div class="dropdown-item text-center text-danger py-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <small>Error loading notifications</small>
                        <br><small class="text-muted">${error.message}</small>
                    </div>
                </li>
            `;
        });
}

function updateNotificationCount() {
    fetch('api_notifications.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const dropdown = document.getElementById('notificationDropdown');
                        if (dropdown) {
                            const span = document.createElement('span');
                            span.id = 'notification-badge';
                            span.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            span.textContent = data.count > 9 ? '9+' : data.count;
                            dropdown.appendChild(span);
                        }
                    } else {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                        badge.style.display = 'block';
                    }
                } else {
                    if (badge) {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification count:', error);
        });
}

function markAsRead(notificationId) {
    if (!notificationId) return;
    
    fetch('api_notifications.php?action=read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                // Reload notifications after a short delay to show updated state
                setTimeout(() => {
                    loadNotifications();
                }, 300);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Smooth scroll for anchor links
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

