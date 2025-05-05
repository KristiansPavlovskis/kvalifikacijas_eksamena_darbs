<?php
session_start();

$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$username = $logged_in ? $_SESSION["username"] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Membership</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="lietotaja-view.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
            --success: #00cc66;
            --warning: #ffa700;
            --info: #0099ff;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }
        
        .membership-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .membership-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .membership-header h1 {
            font-family: 'Koulen', sans-serif;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            display: inline-block;
        }
        
        .membership-header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary);
            border-radius: 2px;
        }
        
        .membership-header p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 1.5rem auto;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.8;
        }
        
        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .pricing-card {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), #ff7070);
        }
        
        .pricing-card.standard::before {
            background: linear-gradient(90deg, #ffa700, #ffcc00);
        }
        
        .pricing-card.advanced::before {
            background: linear-gradient(90deg, #00cc66, #00ff80);
        }
        
        .best-offer {
            position: absolute;
            top: 15px;
            right: -35px;
            background-color: #ffa700;
            color: white;
            padding: 5px 40px;
            font-size: 0.8rem;
            font-weight: 600;
            transform: rotate(45deg);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        
        .plan-name {
            font-family: 'Koulen', sans-serif;
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            letter-spacing: 1px;
        }
        
        .plan-price {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        
        .pricing-card.standard .plan-price {
            color: #ffa700;
        }
        
        .pricing-card.advanced .plan-price {
            color: #00cc66;
        }
        
        .plan-price span {
            font-size: 1rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.6);
            display: block;
            margin-top: 0.5rem;
        }
        
        .features {
            margin-bottom: 2rem;
        }
        
        .feature {
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            position: relative;
            padding-left: 25px;
            text-align: left;
        }
        
        .feature::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }
        
        .pricing-card.standard .feature::before {
            color: #ffa700;
        }
        
        .pricing-card.advanced .feature::before {
            color: #00cc66;
        }
        
        .feature.disabled {
            color: rgba(255, 255, 255, 0.4);
            text-decoration: line-through;
        }
        
        .feature.disabled::before {
            content: '✕';
            color: rgba(255, 77, 77, 0.7);
        }
        
        .join-button {
            background: linear-gradient(90deg, var(--primary), #ff7070);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.3);
        }
        
        .pricing-card.standard .join-button {
            background: linear-gradient(90deg, #ffa700, #ffcc00);
            box-shadow: 0 5px 15px rgba(255, 167, 0, 0.3);
        }
        
        .pricing-card.advanced .join-button {
            background: linear-gradient(90deg, #00cc66, #00ff80);
            box-shadow: 0 5px 15px rgba(0, 204, 102, 0.3);
        }
        
        .join-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 77, 77, 0.4);
        }
        
        .pricing-card.standard .join-button:hover {
            box-shadow: 0 8px 20px rgba(255, 167, 0, 0.4);
        }
        
        .pricing-card.advanced .join-button:hover {
            box-shadow: 0 8px 20px rgba(0, 204, 102, 0.4);
        }
        
        .membership-benefits {
            margin-top: 5rem;
            text-align: center;
        }
        
        .benefits-title {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .benefit-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
        }
        
        .benefit-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .benefit-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: white;
        }
        
        .benefit-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }
        
        .testimonials {
            margin-top: 5rem;
            text-align: center;
        }
        
        .testimonials-title {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .testimonial-card {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
            position: relative;
        }
        
        .testimonial-text {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            font-style: italic;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .author-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            border: 2px solid var(--primary);
        }
        
        .author-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .author-info h4 {
            font-size: 1.1rem;
            color: white;
            margin-bottom: 0.2rem;
        }
        
        .author-info p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .faq-section {
            margin-top: 5rem;
        }
        
        .faq-title {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }
        
        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: white;
            transition: background-color 0.3s;
        }
        
        .faq-question:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .faq-question i {
            color: var(--primary);
            transition: transform 0.3s;
        }
        
        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .faq-answer-content {
            padding-bottom: 1.5rem;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .faq-item.active .faq-answer {
            max-height: 500px;
        }
        
        .cta-section {
            margin-top: 5rem;
            text-align: center;
            background: linear-gradient(145deg, rgba(255, 77, 77, 0.1), rgba(255, 77, 77, 0.2));
            border-radius: 15px;
            padding: 3rem 2rem;
        }
        
        .cta-title {
            font-family: 'Koulen', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .cta-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-button {
            background: linear-gradient(90deg, var(--primary), #ff7070);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.3);
            display: inline-block;
            text-decoration: none;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 77, 77, 0.4);
        }
        
        @media (max-width: 768px) {
            .membership-header h1 {
                font-size: 2.5rem;
            }
            
            .membership-header p {
                font-size: 1rem;
            }
            
            .pricing-cards {
                gap: 1.5rem;
            }
            
            .pricing-card {
                padding: 2rem 1.5rem;
            }
            
            .plan-name {
                font-size: 1.8rem;
            }
            
            .plan-price {
                font-size: 2.5rem;
            }
            
            .benefits-title, .testimonials-title, .faq-title, .cta-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    <div class="membership-container">
        <div class="membership-header">
            <h1>MEMBERSHIP PLANS</h1>
            <p>Unlock your full fitness potential with our premium membership options. Choose the plan that fits your goals and take your fitness journey to the next level.</p>
        </div>

        <div class="pricing-cards">
            <div class="pricing-card basic">
                <h2 class="plan-name">BASIC</h2>
                <div class="plan-price">$0 <span>PER MONTH</span></div>
                <div class="features">
                    <div class="feature">Create Profile</div>
                    <div class="feature">Post Activities</div>
                    <div class="feature">Receive Messages</div>
                    <div class="feature">Basic Badges</div>
                    <div class="feature disabled">Send Friendship Requests</div>
                    <div class="feature disabled">Send Messages</div>
                    <div class="feature disabled">Earn More Badges</div>
                    <div class="feature disabled">Post Images</div>
                </div>
                <button class="join-button" onclick="window.location.href='register.php'">Join Now</button>
            </div>

            <div class="pricing-card standard">
                <div class="best-offer">BEST OFFER</div>
                <h2 class="plan-name">STANDARD</h2>
                <div class="plan-price">$5 <span>PER MONTH</span></div>
                <div class="features">
                    <div class="feature">Create Profile</div>
                    <div class="feature">Post Activities</div>
                    <div class="feature">Receive Messages</div>
                    <div class="feature">Basic Badges</div>
                    <div class="feature">Send Friendship Requests</div>
                    <div class="feature">Send Messages</div>
                    <div class="feature disabled">Earn More Badges</div>
                    <div class="feature disabled">Post Images</div>
                </div>
                <button 
                    class="join-button" 
                    onclick="checkout('price_YOUR_STANDARD_PRICE_ID')"
                >
                    Join Now
                </button>
            </div>

            <div class="pricing-card advanced">
                <h2 class="plan-name">ADVANCED</h2>
                <div class="plan-price">$12 <span>PER MONTH</span></div>
                <div class="features">
                    <div class="feature">Create Profile</div>
                    <div class="feature">Post Activities</div>
                    <div class="feature">Receive Messages</div>
                    <div class="feature">Basic Badges</div>
                    <div class="feature">Send Friendship Requests</div>
                    <div class="feature">Send Messages</div>
                    <div class="feature">Earn More Badges</div>
                    <div class="feature">Post Images</div>
                </div>
                <button 
                    class="join-button" 
                    onclick="checkout('price_YOUR_ADVANCED_PRICE_ID')"
                >
                    Join Now
                </button>
            </div>
        </div>

        <div class="membership-benefits">
            <h2 class="benefits-title">MEMBERSHIP BENEFITS</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3 class="benefit-title">Personalized Workouts</h3>
                    <p class="benefit-description">Get access to customized workout plans tailored to your fitness goals and experience level.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="benefit-title">Nutrition Guidance</h3>
                    <p class="benefit-description">Receive expert nutrition advice and meal plans to complement your fitness routine.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="benefit-title">Progress Tracking</h3>
                    <p class="benefit-description">Track your fitness journey with detailed analytics and visualizations of your progress.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="benefit-title">Community Support</h3>
                    <p class="benefit-description">Join a community of like-minded individuals to share experiences and stay motivated.</p>
                </div>
            </div>
        </div>

        <div class="testimonials">
            <h2 class="testimonials-title">WHAT OUR MEMBERS SAY</h2>
            <div class="testimonial-card">
                <p class="testimonial-text">"GYMVERSE has completely transformed my fitness journey. The personalized workouts and nutrition guidance have helped me achieve results I never thought possible. The community support keeps me motivated every day!"</p>
                <div class="testimonial-author">
                    <div class="author-image">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="John Doe">
                    </div>
                    <div class="author-info">
                        <h4>John Doe</h4>
                        <p>Advanced Member, 8 months</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="faq-section">
            <h2 class="faq-title">FREQUENTLY ASKED QUESTIONS</h2>
            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I upgrade my membership?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        You can upgrade your membership at any time by visiting the membership page and selecting your desired plan. Your benefits will be upgraded immediately after payment.
                    </div>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I cancel my subscription?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Yes, you can cancel your subscription at any time. Your membership benefits will continue until the end of your current billing period.
                    </div>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <span>What payment methods do you accept?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        We accept all major credit cards, PayPal, and Apple Pay for membership payments. All transactions are secure and encrypted.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
    <script>
        const stripe = Stripe('pk_test_51NSmyBJcCgClprXkJYxiTNHkRm3GqQINWJM9S64wGQ0k5ZFzLanviduScEbhB5260gzFJDVk73WbAYhmv2k3diT500CkGhny91');

        function checkout(priceId) {
            stripe.redirectToCheckout({
                lineItems: [{
                    price: priceId,
                    quantity: 1
                }],
                mode: 'subscription',
                successUrl: window.location.origin + '/success.php',
                cancelUrl: window.location.origin + '/membership.php',
            })
            .then(function (result) {
                if (result.error) {
                    alert(result.error.message);
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    item.classList.toggle('active');
                    
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 