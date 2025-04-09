<?php
// Set page title
$pageTitle = "Mail";
$additionalHead = '<link rel="stylesheet" href="/assets/css/mail.css">';

// Include sidebar
include_once 'includes/sidebar.php';

// Get current tab (inbox, sent, drafts, trash)
$mailTab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';

// Function to get mail count for each tab (placeholder)
function getMailCount($tab) {
    // This would normally query the database
    switch ($tab) {
        case 'inbox': return rand(5, 20);
        case 'sent': return rand(3, 15);
        case 'drafts': return rand(0, 5);
        case 'trash': return rand(0, 10);
        default: return 0;
    }
}

// Sample mail data (placeholder)
$mailItems = [
    [
        'id' => 1,
        'sender' => 'John Doe',
        'sender_email' => 'john.doe@example.com',
        'subject' => 'Welcome to the platform',
        'content' => 'Hello, welcome to our platform! We are excited to have you on board...',
        'date' => '2023-06-15 14:30:00',
        'read' => true,
        'starred' => false,
        'attachments' => []
    ],
    [
        'id' => 2,
        'sender' => 'Jane Smith',
        'sender_email' => 'jane.smith@example.com',
        'subject' => 'New product features',
        'content' => 'We are excited to announce some new features that will be available next week...',
        'date' => '2023-06-14 09:15:00',
        'read' => false,
        'starred' => true,
        'attachments' => ['document.pdf', 'image.jpg']
    ],
    [
        'id' => 3,
        'sender' => 'Support Team',
        'sender_email' => 'support@example.com',
        'subject' => 'Your support ticket #45892',
        'content' => 'We have received your support request and our team is working on it...',
        'date' => '2023-06-13 16:45:00',
        'read' => false,
        'starred' => false,
        'attachments' => []
    ],
    [
        'id' => 4,
        'sender' => 'Marketing Department',
        'sender_email' => 'marketing@example.com',
        'subject' => 'Upcoming promotion campaign',
        'content' => 'Please review the attached documents for our upcoming summer promotion campaign...',
        'date' => '2023-06-12 11:20:00',
        'read' => true,
        'starred' => false,
        'attachments' => ['campaign_details.docx', 'graphics.zip']
    ],
    [
        'id' => 5,
        'sender' => 'System Notification',
        'sender_email' => 'noreply@system.com',
        'subject' => 'Security alert',
        'content' => 'Your account was accessed from a new device. If this was not you, please contact support immediately...',
        'date' => '2023-06-11 22:05:00',
        'read' => true,
        'starred' => true,
        'attachments' => []
    ]
];

// Get selected mail (if any)
$selectedMailId = isset($_GET['mail']) ? intval($_GET['mail']) : null;
$selectedMail = null;
if ($selectedMailId) {
    foreach ($mailItems as $mail) {
        if ($mail['id'] == $selectedMailId) {
            $selectedMail = $mail;
            break;
        }
    }
}

// Determine if compose mode is active
$composeMode = isset($_GET['compose']) && $_GET['compose'] == '1';
?>

<div class="mail-container">
    <!-- Mail Sidebar -->
    <div class="mail-sidebar">
        <button class="compose-btn" onclick="location.href='?compose=1'">
            <i class="fas fa-plus"></i> Compose
        </button>
        
        <ul class="mail-nav">
            <li class="<?php echo $mailTab == 'inbox' ? 'active' : ''; ?>">
                <a href="?tab=inbox">
                    <i class="fas fa-inbox"></i> 
                    <span>Inbox</span>
                    <span class="mail-count"><?php echo getMailCount('inbox'); ?></span>
                </a>
            </li>
            <li class="<?php echo $mailTab == 'sent' ? 'active' : ''; ?>">
                <a href="?tab=sent">
                    <i class="fas fa-paper-plane"></i> 
                    <span>Sent</span>
                    <span class="mail-count"><?php echo getMailCount('sent'); ?></span>
                </a>
            </li>
            <li class="<?php echo $mailTab == 'drafts' ? 'active' : ''; ?>">
                <a href="?tab=drafts">
                    <i class="fas fa-file-alt"></i> 
                    <span>Drafts</span>
                    <span class="mail-count"><?php echo getMailCount('drafts'); ?></span>
                </a>
            </li>
            <li class="<?php echo $mailTab == 'trash' ? 'active' : ''; ?>">
                <a href="?tab=trash">
                    <i class="fas fa-trash-alt"></i> 
                    <span>Trash</span>
                    <span class="mail-count"><?php echo getMailCount('trash'); ?></span>
                </a>
            </li>
        </ul>
        
        <div class="mail-labels">
            <h4>Labels</h4>
            <ul>
                <li><span class="label-dot" style="background-color: #ff5722;"></span> Important</li>
                <li><span class="label-dot" style="background-color: #4caf50;"></span> Work</li>
                <li><span class="label-dot" style="background-color: #2196f3;"></span> Personal</li>
                <li><span class="label-dot" style="background-color: #9c27b0;"></span> Projects</li>
            </ul>
        </div>
    </div>
    
    <!-- Mail Content Area -->
    <div class="mail-content">
        <?php if ($composeMode): ?>
            <!-- Compose Email Interface -->
            <div class="mail-compose">
                <div class="compose-header">
                    <h2>New Message</h2>
                    <button class="close-compose" onclick="location.href='?tab=<?php echo $mailTab; ?>'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form class="compose-form" action="#" method="post">
                    <div class="form-group">
                        <label for="recipient">To:</label>
                        <input type="email" id="recipient" name="recipient" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cc">CC:</label>
                        <input type="email" id="cc" name="cc" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea id="message" name="message" class="form-control" rows="12" required></textarea>
                    </div>
                    
                    <div class="compose-actions">
                        <div class="attachments">
                            <button type="button" class="attachment-btn">
                                <i class="fas fa-paperclip"></i> Attach Files
                            </button>
                            <input type="file" id="file-upload" multiple style="display: none;">
                        </div>
                        
                        <div class="send-options">
                            <button type="button" class="btn btn-secondary">Save Draft</button>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php elseif ($selectedMail): ?>
            <!-- View Selected Email -->
            <div class="mail-view">
                <div class="mail-view-header">
                    <h2><?php echo htmlspecialchars($selectedMail['subject']); ?></h2>
                    
                    <div class="mail-actions">
                        <button class="btn btn-icon" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button class="btn btn-icon" title="Forward">
                            <i class="fas fa-share"></i>
                        </button>
                        <button class="btn btn-icon" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <button class="btn btn-icon" title="Mark as Unread">
                            <i class="fas fa-envelope"></i>
                        </button>
                        <button class="btn btn-icon" title="Star">
                            <i class="fas <?php echo $selectedMail['starred'] ? 'fa-star' : 'fa-star-o'; ?>"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mail-sender-info">
                    <div class="sender-avatar">
                        <div class="avatar-placeholder">
                            <?php echo substr($selectedMail['sender'], 0, 1); ?>
                        </div>
                    </div>
                    <div class="sender-details">
                        <div class="sender-name">
                            <?php echo htmlspecialchars($selectedMail['sender']); ?> 
                            <span class="sender-email">&lt;<?php echo htmlspecialchars($selectedMail['sender_email']); ?>&gt;</span>
                        </div>
                        <div class="sender-date">
                            <?php echo date('M d, Y h:i A', strtotime($selectedMail['date'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="mail-content-body">
                    <?php echo nl2br(htmlspecialchars($selectedMail['content'])); ?>
                </div>
                
                <?php if (!empty($selectedMail['attachments'])): ?>
                <div class="mail-attachments">
                    <h4>Attachments (<?php echo count($selectedMail['attachments']); ?>)</h4>
                    <div class="attachment-list">
                        <?php foreach ($selectedMail['attachments'] as $attachment): ?>
                            <div class="attachment-item">
                                <div class="attachment-icon">
                                    <i class="fas <?php echo pathinfo($attachment, PATHINFO_EXTENSION) == 'pdf' ? 'fa-file-pdf' : 
                                        (pathinfo($attachment, PATHINFO_EXTENSION) == 'docx' ? 'fa-file-word' : 
                                        (pathinfo($attachment, PATHINFO_EXTENSION) == 'zip' ? 'fa-file-archive' : 'fa-file-image')); ?>"></i>
                                </div>
                                <div class="attachment-info">
                                    <span class="attachment-name"><?php echo htmlspecialchars($attachment); ?></span>
                                </div>
                                <div class="attachment-actions">
                                    <button class="btn btn-sm btn-secondary">Download</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mail-reply-section">
                    <div class="reply-form">
                        <textarea placeholder="Write your reply here..." rows="3" class="form-control"></textarea>
                        <div class="reply-actions">
                            <button class="btn btn-primary">Reply</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Mail List View -->
            <div class="mail-list-container">
                <div class="mail-list-header">
                    <h2><?php echo ucfirst($mailTab); ?></h2>
                    
                    <div class="mail-list-actions">
                        <div class="mail-search">
                            <input type="text" placeholder="Search mail..." class="search-input">
                            <button class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <div class="mail-filters">
                            <button class="filter-btn">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button class="refresh-btn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mail-list">
                    <?php foreach ($mailItems as $mail): ?>
                        <a href="?tab=<?php echo $mailTab; ?>&mail=<?php echo $mail['id']; ?>" 
                           class="mail-item <?php echo !$mail['read'] ? 'unread' : ''; ?>">
                            <div class="mail-item-checkbox">
                                <input type="checkbox" id="mail-<?php echo $mail['id']; ?>">
                                <label for="mail-<?php echo $mail['id']; ?>"></label>
                            </div>
                            
                            <div class="mail-item-star">
                                <i class="fas <?php echo $mail['starred'] ? 'fa-star' : 'fa-star-o'; ?>"></i>
                            </div>
                            
                            <div class="mail-item-sender">
                                <?php echo htmlspecialchars($mail['sender']); ?>
                            </div>
                            
                            <div class="mail-item-content">
                                <div class="mail-item-subject">
                                    <?php echo htmlspecialchars($mail['subject']); ?>
                                </div>
                                <div class="mail-item-snippet">
                                    <?php echo htmlspecialchars(substr($mail['content'], 0, 80) . '...'); ?>
                                </div>
                            </div>
                            
                            <div class="mail-item-attachment">
                                <?php if (!empty($mail['attachments'])): ?>
                                    <i class="fas fa-paperclip"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mail-item-date">
                                <?php echo date('M d', strtotime($mail['date'])); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="mail-pagination">
                    <span>Showing 1-5 of 27 items</span>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                        <span class="pagination-page">1</span>
                        <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attachment button functionality
    const attachmentBtn = document.querySelector('.attachment-btn');
    const fileUpload = document.getElementById('file-upload');
    
    if (attachmentBtn && fileUpload) {
        attachmentBtn.addEventListener('click', function() {
            fileUpload.click();
        });
        
        fileUpload.addEventListener('change', function() {
            // Show selected files
            const files = this.files;
            if (files.length > 0) {
                let fileNames = Array.from(files).map(file => file.name).join(', ');
                alert('Selected files: ' + fileNames);
                // In a real implementation, you would display these files in the UI
            }
        });
    }
    
    // Star functionality
    const starButtons = document.querySelectorAll('.fa-star, .fa-star-o');
    starButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.toggle('fa-star');
            this.classList.toggle('fa-star-o');
            // In a real implementation, you would update the starred status in the database
        });
    });
    
    // Checkbox functionality
    const checkboxes = document.querySelectorAll('.mail-item-checkbox input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();
            // In a real implementation, you would toggle selection state
        });
    });
});
</script>
 