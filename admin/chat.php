<?php



require_once "../assets/db_connection.php";


$pageTitle = "Admin Chat";
$bodyClass = "admin-page dark-mode";

$additionalHead = '<style>
.chat-container {
    display: flex;
    height: calc(100vh - 200px);
    min-height: 500px;
    margin-bottom: 1.5rem;
    border-radius: 10px;
    overflow: hidden;
    background-color: var(--card-bg);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.contacts-sidebar {
    width: 280px;
    background-color: var(--card-bg);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    flex-direction: column;
}

.contacts-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.search-contact {
    position: relative;
    margin-bottom: 0.5rem;
}

.search-contact input {
    width: 100%;
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background-color: rgba(255, 255, 255, 0.05);
    color: var(--text-color);
}

.search-contact i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sidebar-icon);
}

.contact-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem 0;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
    border-left: 3px solid transparent;
}

.contact-item:hover, .contact-item.active {
    background-color: rgba(255, 255, 255, 0.05);
    border-left-color: var(--primary-color);
}

.contact-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 0.75rem;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.contact-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-last-message {
    font-size: 0.75rem;
    color: var(--sidebar-icon);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    min-width: 50px;
}

.contact-time {
    font-size: 0.75rem;
    color: var(--sidebar-icon);
    margin-bottom: 0.25rem;
}

.contact-unread {
    display: inline-block;
    width: 18px;
    height: 18px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: rgba(0, 0, 0, 0.1);
}

.chat-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background-color: var(--card-bg);
}

.chat-title {
    flex: 1;
}

.chat-title h3 {
    margin: 0;
    font-size: 1rem;
}

.chat-title p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--sidebar-icon);
}

.chat-actions a {
    color: var(--sidebar-icon);
    font-size: 1.25rem;
    margin-left: 1rem;
    transition: color 0.2s;
}

.chat-actions a:hover {
    color: var(--text-color);
}

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.message {
    display: flex;
    margin-bottom: 1rem;
    max-width: 75%;
}

.message.outgoing {
    margin-left: auto;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 0.75rem;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.message-content {
    background-color: rgba(255, 255, 255, 0.05);
    padding: 0.75rem 1rem;
    border-radius: 10px;
    position: relative;
}

.message.outgoing .message-content {
    background-color: var(--primary-color);
}

.message-text {
    margin-bottom: 0.25rem;
}

.message-time {
    font-size: 0.7rem;
    color: var(--sidebar-icon);
    text-align: right;
}

.message.outgoing .message-time {
    color: rgba(255, 255, 255, 0.7);
}

.chat-input-container {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background-color: var(--card-bg);
}

.chat-input {
    display: flex;
    align-items: center;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 24px;
    padding: 0.5rem;
}

.chat-input-actions {
    display: flex;
    margin-right: 0.5rem;
}

.chat-input-actions a {
    color: var(--sidebar-icon);
    font-size: 1.25rem;
    margin-left: 0.5rem;
    transition: color 0.2s;
}

.chat-input-actions a:hover {
    color: var(--text-color);
}

.chat-input input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 0.5rem;
    color: var(--text-color);
}

.chat-input button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.chat-input button:hover {
    background-color: var(--primary-dark);
}

@media (max-width: 768px) {
    .chat-container {
        flex-direction: column;
        height: calc(100vh - 150px);
    }
    
    .contacts-sidebar {
        width: 100%;
        height: 300px;
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
}
</style>';


require_once "includes/sidebar.php";
?>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="dashboard-title">
        <h1>Chat Center</h1>
        <p>Connect with users, trainers, and team members</p>
    </div>
    <div>
        <button class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> New Conversation
        </button>
    </div>
</div>

<!-- Chat Container -->
<div class="chat-container">
    <!-- Contacts Sidebar -->
    <div class="contacts-sidebar">
        <div class="contacts-header">
            <div class="search-contact">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search contacts...">
            </div>
            <div class="btn-group btn-group-sm w-100 mt-2">
                <button class="btn btn-outline-secondary active flex-grow-1">All Chats</button>
                <button class="btn btn-outline-secondary flex-grow-1">Unread</button>
                <button class="btn btn-outline-secondary flex-grow-1">Users</button>
            </div>
        </div>
        <div class="contact-list">
            <!-- Active Contact -->
            <div class="contact-item active">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
                </div>
                <div class="contact-info">
                    <div class="contact-name">Sarah Johnson</div>
                    <div class="contact-last-message">I just completed the HIIT workout! It was intense!</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">12:45 PM</div>
                    <div class="contact-unread">3</div>
                </div>
            </div>
            
            <!-- Other Contacts -->
            <div class="contact-item">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user2.jpg" alt="David Chen">
                </div>
                <div class="contact-info">
                    <div class="contact-name">David Chen</div>
                    <div class="contact-last-message">Is the nutrition plan available for download?</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Yesterday</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user3.jpg" alt="Emma Williams">
                </div>
                <div class="contact-info">
                    <div class="contact-name">Emma Williams</div>
                    <div class="contact-last-message">Thank you for your support!</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Yesterday</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user4.jpg" alt="Michael Rodriguez">
                </div>
                <div class="contact-info">
                    <div class="contact-name">Michael Rodriguez</div>
                    <div class="contact-last-message">I'm having trouble with my account</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Jul 28</div>
                    <div class="contact-unread">1</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <span>TW</span>
                </div>
                <div class="contact-info">
                    <div class="contact-name">Trainer Weekly</div>
                    <div class="contact-last-message">New workout schedule for next week</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Jul 25</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user5.jpg" alt="Olivia Martinez">
                </div>
                <div class="contact-info">
                    <div class="contact-name">Olivia Martinez</div>
                    <div class="contact-last-message">When will the new equipment arrive?</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Jul 22</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/user6.jpg" alt="James Wilson">
                </div>
                <div class="contact-info">
                    <div class="contact-name">James Wilson</div>
                    <div class="contact-last-message">Payment issue resolved, thank you</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Jul 20</div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-avatar">
                    <span>SC</span>
                </div>
                <div class="contact-info">
                    <div class="contact-name">Support Channel</div>
                    <div class="contact-last-message">Multiple users: Discussing new features</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">Jul 18</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chat Main Area -->
    <div class="chat-main">
        <div class="chat-header">
            <div class="contact-avatar">
                <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
            </div>
            <div class="chat-title">
                <h3>Sarah Johnson</h3>
                <p>Online • Last active 5m ago</p>
            </div>
            <div class="chat-actions">
                <a href="#"><i class="fas fa-phone"></i></a>
                <a href="#"><i class="fas fa-video"></i></a>
                <a href="#"><i class="fas fa-info-circle"></i></a>
            </div>
        </div>
        
        <div class="chat-messages">
            <!-- Time Separator -->
            <div class="time-separator text-center mb-3">
                <span style="color: var(--sidebar-icon); font-size: 0.75rem; background-color: rgba(0, 0, 0, 0.2); padding: 0.25rem 0.75rem; border-radius: 12px;">Today, 12:30 PM</span>
            </div>
            
            <!-- Incoming Message -->
            <div class="message">
                <div class="message-avatar">
                    <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
                </div>
                <div class="message-content">
                    <div class="message-text">Hi there! I just completed the HIIT workout you recommended!</div>
                    <div class="message-time">12:30 PM</div>
                </div>
            </div>
            
            <!-- Outgoing Message -->
            <div class="message outgoing">
                <div class="message-avatar">
                    <span>A</span>
                </div>
                <div class="message-content">
                    <div class="message-text">That's great to hear, Sarah! How did you find it? Was the intensity level good for you?</div>
                    <div class="message-time">12:35 PM</div>
                </div>
            </div>
            
            <!-- Incoming Message -->
            <div class="message">
                <div class="message-avatar">
                    <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
                </div>
                <div class="message-content">
                    <div class="message-text">It was intense! But I managed to complete all sets. I really felt it in my core and legs.</div>
                    <div class="message-time">12:37 PM</div>
                </div>
            </div>
            
            <div class="message">
                <div class="message-avatar">
                    <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
                </div>
                <div class="message-content">
                    <div class="message-text">I have a question about the nutrition plan though. Is it necessary to follow it strictly, or can I make some substitutions?</div>
                    <div class="message-time">12:38 PM</div>
                </div>
            </div>
            
            <!-- Outgoing Message -->
            <div class="message outgoing">
                <div class="message-avatar">
                    <span>A</span>
                </div>
                <div class="message-content">
                    <div class="message-text">That's what we like to hear! Regarding the nutrition plan, it's designed to complement the workout program, but you can definitely make substitutions as long as you maintain similar macronutrient ratios.</div>
                    <div class="message-time">12:41 PM</div>
                </div>
            </div>
            
            <!-- Incoming Message -->
            <div class="message">
                <div class="message-avatar">
                    <img src="/assets/images/avatars/user1.jpg" alt="Sarah Johnson">
                </div>
                <div class="message-content">
                    <div class="message-text">Perfect! That helps a lot. I'll continue with the program and keep you updated on my progress.</div>
                    <div class="message-time">12:45 PM</div>
                </div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input">
                <div class="chat-input-actions">
                    <a href="#"><i class="fas fa-paperclip"></i></a>
                    <a href="#"><i class="far fa-smile"></i></a>
                </div>
                <input type="text" placeholder="Type a message...">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.querySelector('.chat-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    const contactItems = document.querySelectorAll('.contact-item');
    contactItems.forEach(item => {
        item.addEventListener('click', () => {
            contactItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });
    
    const chatInput = document.querySelector('.chat-input input');
    const sendButton = document.querySelector('.chat-input button');
    
    const sendMessage = () => {
        const message = chatInput.value.trim();
        if (message) {
            console.log('Sending message:', message);
            
            const now = new Date();
            const timeString = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
            
            const newMessage = document.createElement('div');
            newMessage.className = 'message outgoing';
            newMessage.innerHTML = `
                <div class="message-avatar">
                    <span>A</span>
                </div>
                <div class="message-content">
                    <div class="message-text">${message}</div>
                    <div class="message-time">${timeString}</div>
                </div>
            `;
            
            messagesContainer.appendChild(newMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            chatInput.value = '';
        }
    };
    
    sendButton.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>

</div> <!-- End of page-content -->
</div> <!-- End of main-content -->
</div> <!-- End of admin-wrapper -->
</body>
</html> 