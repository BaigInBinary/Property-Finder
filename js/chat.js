let activeUserId = null;
const unsentMessages = {};

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM loaded.");
  fetchUsers();
  updateLastActive();

  setInterval(refreshActiveChat, 2000); // Faster refresh (2 seconds)
  setInterval(updateLastActive, 30000);

  ["click", "mousemove", "keydown"].forEach((event) => {
    document.addEventListener(event, updateLastActive);
  });
});

async function fetchUsers() {
  try {
    console.log("Fetching users...");
    const res = await fetch("backend/fetch-users.php");
    console.log("Response status:", res.status);
    const data = await res.json();
    console.log("Users data:", data);

    if (data.status === "success") {
      const userList = document.getElementById("userList");
      const chatTabsContent = document.getElementById("chatTabsContent");

      userList.innerHTML = "";
      chatTabsContent.innerHTML = "";

      data.users.forEach((user, index) => {
        const isActive =
          (activeUserId === null && index === 0) || user.id == activeUserId;
        if (isActive) activeUserId = user.id;

        const statusClass = user.is_online ? "status-online" : "status-offline";
        const statusText = formatLastActive(user.last_active, user.is_online);

        userList.insertAdjacentHTML(
          "beforeend",
          `
                    <li class="nav-item">
                        <a class="nav-link ${
                          isActive ? "active" : ""
                        }" id="user-${
            user.id
          }-tab" data-bs-toggle="pill" href="#chat-${user.id}" role="tab">
                            <div class="d-flex align-items-center">
                                <img src="${(
                                  user.picture || "https://placehold.co/40x40"
                                ).replace(
                                  "../",
                                  ""
                                )}" class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <div class="text-black">${user.name}</div>
                                     <span class="ms-2 dot-indicator ${
                                       user.is_online
                                         ? "bg-success"
                                         : "bg-danger"
                                     }"></span>
                                    <small class="text-muted">${statusText}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                `
        );

        const savedInput = unsentMessages[user.id] || "";
        chatTabsContent.insertAdjacentHTML(
          "beforeend",
          `
                    <div class="tab-pane fade ${
                      isActive ? "show active" : ""
                    }" id="chat-${user.id}" role="tabpanel">
                        <div class="chat-header p-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <img src="${(
                                  user.picture || "https://placehold.co/40x40"
                                ).replace(
                                  "../",
                                  ""
                                )}" class="rounded-circle me-2" width="40" height="40">
                                <div class="ms-3">
                                    <h6 class="mb-0">${user.name}</h6>
                                    <small><span class="status-badge ${statusClass}"></span> ${statusText}</small>
                                </div>
                            </div>
                        </div>
                        <div class="messages-area p-3"></div>
                        <div class="chat-input p-3 mt-auto">
                            <form class="d-flex gap-2" onsubmit="return sendMessage(event, ${
                              user.id
                            })">
                                <input type="text" class="form-control message-input" placeholder="Type your message..." value="${savedInput}">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                            </form>
                        </div>
                    </div>
                `
        );

        document
          .querySelector(`#user-${user.id}-tab`)
          .addEventListener("click", () => {
            activeUserId = user.id;
            loadChatMessages(user.id, true);
          });

        if (isActive) {
          loadChatMessages(user.id, true);
        }
      });
    }
  } catch (err) {
    console.error("Error fetching users:", err);
  }
}

function formatLastActive(lastActive, isOnline) {
  if (isOnline) return "Online";

  const lastDate = new Date(lastActive);
  const now = new Date();
  const diffMs = now - lastDate;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays >= 1) {
    return `${diffDays} day${diffDays > 1 ? "s" : ""} ago`;
  } else {
    return `Last online: ${lastDate.toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    })}`;
  }
}

async function loadChatMessages(userId, showLoader = false) {
  try {
    console.log("Loading messages for user ID:", userId);
    const messagesArea = document.querySelector(
      `#chat-${userId} .messages-area`
    );

    if (showLoader && messagesArea.innerHTML.trim() === "") {
      messagesArea.innerHTML = `
                <div class="text-center p-2">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
    }

    const res = await fetch(`backend/fetch-messages.php?userId=${userId}`);
    const data = await res.json();

    if (data.status === "success") {
      if (data.messages.length > 0) {
        messagesArea.innerHTML = "";
        data.messages.forEach((msg) => {
          const isSent = msg.is_sent_by_current_user;
          messagesArea.innerHTML += `
                        <div class="message ${
                          isSent ? "message-sent" : "message-received"
                        }">
                            <div class="card ${
                              isSent ? "bg-primary text-white" : ""
                            }">
                                <div class="card-body py-2">
                                    <p class="mb-0">${msg.message}</p>
                                    <small class="${
                                      isSent ? "text-white-50" : "text-muted"
                                    }">${msg.time}</small>
                                </div>
                            </div>
                        </div>
                    `;
        });

        // ✅ Auto-scroll to the latest message
        messagesArea.scrollTop = messagesArea.scrollHeight;
      } else if (messagesArea.innerHTML.trim() === "" || showLoader) {
        messagesArea.innerHTML =
          '<div class="text-center text-muted">No messages yet.</div>';
      }
    }
  } catch (err) {
    console.error("Error loading chat:", err);
  }
}

function refreshActiveChat() {
  if (activeUserId !== null) {
    loadChatMessages(activeUserId, false);
  }
}

async function sendMessage(event, userId) {
  event.preventDefault();
  const chatTab = document.querySelector(`#chat-${userId}`);
  const input = chatTab.querySelector(".message-input");
  const message = input.value.trim();

  if (!message) return false;

  try {
    const res = await fetch("backend/send-message.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ receiver_id: userId, message }),
    });

    const data = await res.json();
    if (data.status === "success") {
      input.value = "";
      unsentMessages[userId] = "";
      loadChatMessages(userId);
    } else {
      console.error("Failed to send message:", data.message);
    }
  } catch (err) {
    console.error("Error sending message:", err);
  }
  return false;
}

document.addEventListener("input", (e) => {
  if (e.target.classList.contains("message-input")) {
    const parentTab = e.target.closest(".tab-pane");
    if (parentTab) {
      const userId = parentTab.id.replace("chat-", "");
      unsentMessages[userId] = e.target.value;
    }
  }
});

async function updateLastActive() {
  try {
    await fetch("backend/update-last-active.php", { method: "POST" });
  } catch (err) {
    console.error("Error updating last active:", err);
  }
}
