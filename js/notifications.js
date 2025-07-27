class NotificationsManager {
  constructor() {
    this.notifications = [];
    this.unreadCount = 0;
    this.init();
  }

  init() {
    // Initialize notifications dropdown
    this.dropdown = document.getElementById("notificationsDropdown");
    this.badge = document.querySelector(".notification-badge");
    this.list = document.querySelector(".notifications-list");

    // Load notifications from localStorage or fetch from server
    this.loadNotifications();

    // Add event listeners
    this.setupEventListeners();
  }

  loadNotifications() {
    // In a real app, this would fetch from an API
    // For demo, we'll use sample notifications
    this.notifications = [
      {
        id: 1,
        type: "message",
        title: "New Message",
        text: "You have received a new message from Sarah regarding your property listing.",
        timestamp: new Date(Date.now() - 1000 * 60 * 5), // 5 minutes ago
        unread: true,
        link: "/chat.html",
      },
      {
        id: 2,
        type: "alert",
        title: "Listing Update",
        text: 'Your property listing "Modern Apartment" has been approved.',
        timestamp: new Date(Date.now() - 1000 * 60 * 30), // 30 minutes ago
        unread: true,
        link: "/listings.php",
      },
      {
        id: 3,
        type: "success",
        title: "Review Response",
        text: "The agent has responded to your review.",
        timestamp: new Date(Date.now() - 1000 * 60 * 60), // 1 hour ago
        unread: false,
        link: "/reviews.html",
      },
      {
        id: 4,
        type: "message",
        title: "Property Inquiry",
        text: "New inquiry received for your listed property at 123 Main Street.",
        timestamp: new Date(Date.now() - 1000 * 60 * 120), // 2 hours ago
        unread: true,
        link: "/listings.php",
      },
      {
        id: 5,
        type: "alert",
        title: "Price Update",
        text: "Price change alert: Similar property in your area has updated their price.",
        timestamp: new Date(Date.now() - 1000 * 60 * 180), // 3 hours ago
        unread: false,
        link: "/listings.php",
      },
      {
        id: 6,
        type: "success",
        title: "Viewing Scheduled",
        text: "Property viewing has been confirmed for tomorrow at 2 PM.",
        timestamp: new Date(Date.now() - 1000 * 60 * 240), // 4 hours ago
        unread: true,
        link: "/dashboard.html",
      },
      {
        id: 7,
        type: "message",
        title: "Agent Response",
        text: "Agent John Smith has responded to your inquiry.",
        timestamp: new Date(Date.now() - 1000 * 60 * 300), // 5 hours ago
        unread: false,
        link: "/chat.html",
      },
      {
        id: 8,
        type: "alert",
        title: "Document Required",
        text: "Please upload the required verification documents.",
        timestamp: new Date(Date.now() - 1000 * 60 * 360), // 6 hours ago
        unread: true,
        link: "/dashboard.html",
      },
      {
        id: 9,
        type: "success",
        title: "Profile Verified",
        text: "Your profile verification is complete. You can now list properties.",
        timestamp: new Date(Date.now() - 1000 * 60 * 420), // 7 hours ago
        unread: false,
        link: "/dashboard.html",
      },
      {
        id: 10,
        type: "message",
        title: "Welcome!",
        text: "Welcome to PropFind! Get started by completing your profile.",
        timestamp: new Date(Date.now() - 1000 * 60 * 480), // 8 hours ago
        unread: false,
        link: "/dashboard.html",
      },
    ];

    this.updateNotificationsList();
    this.updateUnreadCount();
  }

  setupEventListeners() {
    // Mark all as read button
    document.getElementById("markAllRead").addEventListener("click", (e) => {
      e.stopPropagation();
      this.markAllAsRead();
    });

    // Individual notification clicks
    this.list.addEventListener("click", (e) => {
      const notificationItem = e.target.closest(".notification-item");
      if (notificationItem) {
        const id = parseInt(notificationItem.dataset.id);
        this.handleNotificationClick(id);
      }
    });

    // Delete notification buttons
    this.list.addEventListener("click", (e) => {
      if (e.target.matches(".delete-notification")) {
        e.stopPropagation();
        const id = parseInt(e.target.closest(".notification-item").dataset.id);
        this.deleteNotification(id);
      }
    });
  }

  updateNotificationsList() {
    if (this.notifications.length === 0) {
      this.list.innerHTML = `
                <div class="notifications-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications</p>
                </div>
            `;
      return;
    }

    this.list.innerHTML = this.notifications
      .map((notification) => this.createNotificationHTML(notification))
      .join("");
  }

  createNotificationHTML(notification) {
    const timeAgo = this.getTimeAgo(notification.timestamp);
    return `
            <div class="notification-item ${
              notification.unread ? "unread" : ""
            }" 
                 data-id="${notification.id}">
                <div class="notification-icon ${notification.type}">
                    <i class="fas ${this.getIconClass(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-text">${notification.text}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                <button class="btn btn-link btn-sm text-muted delete-notification">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
  }

  getIconClass(type) {
    const icons = {
      message: "fa-envelope",
      alert: "fa-bell",
      success: "fa-check-circle",
    };
    return icons[type] || "fa-bell";
  }

  getTimeAgo(timestamp) {
    const seconds = Math.floor((new Date() - timestamp) / 1000);
    const intervals = {
      year: 31536000,
      month: 2592000,
      week: 604800,
      day: 86400,
      hour: 3600,
      minute: 60,
    };

    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
      const interval = Math.floor(seconds / secondsInUnit);
      if (interval >= 1) {
        return `${interval} ${unit}${interval === 1 ? "" : "s"} ago`;
      }
    }
    return "Just now";
  }

  updateUnreadCount() {
    this.unreadCount = this.notifications.filter((n) => n.unread).length;
    if (this.unreadCount > 0) {
      this.badge.textContent = this.unreadCount;
      this.badge.classList.remove("d-none");
    } else {
      this.badge.classList.add("d-none");
    }
  }

  markAllAsRead() {
    this.notifications.forEach((notification) => {
      notification.unread = false;
    });
    this.updateNotificationsList();
    this.updateUnreadCount();
  }

  handleNotificationClick(id) {
    const notification = this.notifications.find((n) => n.id === id);
    if (notification) {
      notification.unread = false;
      this.updateNotificationsList();
      this.updateUnreadCount();

      // Navigate to the relevant page
      if (notification.link) {
        window.location.href = notification.link;
      }
    }
  }

  deleteNotification(id) {
    this.notifications = this.notifications.filter((n) => n.id !== id);
    this.updateNotificationsList();
    this.updateUnreadCount();
  }

  // Method to add a new notification
  addNotification(notification) {
    this.notifications.unshift({
      id: Date.now(),
      timestamp: new Date(),
      unread: true,
      ...notification,
    });
    this.updateNotificationsList();
    this.updateUnreadCount();
  }
}

// Initialize notifications when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.notificationsManager = new NotificationsManager();
});
