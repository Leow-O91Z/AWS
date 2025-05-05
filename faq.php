<?php
// faq.php)
$faqs = [
    [
        'question' => 'How do I place an order?',
        'answer' => 'Simply browse our products, select the size and color you want, add the item(s) to your cart, and proceed to checkout. Follow the steps to enter your shipping and payment information.'
    ],
    [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We currently accept major credit cards (Visa, MasterCard, American Express) through our secure payment gateway. [Add other methods like PayPal if applicable].'
    ],
    [
        'question' => 'How can I track my order?',
        'answer' => 'Once your order ships, you will receive a confirmation email with a tracking number and a link to the carrier\'s website. You can also check your order status in your account profile if you created one.'
    ],
    [
        'question' => 'What is your return policy?',
        'answer' => 'We offer a 30-day return policy for unworn items in their original packaging. Please visit our [Link to Shipping & Returns Page, e.g., <a href="index.php?page=shipping">Shipping & Returns</a>] page for detailed instructions.'
    ],
    [
        'question' => 'Do you ship internationally?',
        'answer' => 'Currently, we only ship within [Your Country/Region]. We are working on expanding our shipping options in the future.'
    ],
    [
        'question' => 'How do I know what size to order?',
        'answer' => 'We provide size charts on each product page. We recommend comparing the measurements to a pair of shoes you already own or measuring your foot length. If you\'re between sizes, we generally suggest sizing up for comfort.'
    ],
];

?>

<div class="static-page-container faq-page">
    <h1>Frequently Asked Questions</h1>

    <div class="faq-list">
        <?php foreach ($faqs as $index => $faq): ?>
            <details class="faq-item" <?php echo ($index === 0) ? 'open' : ''; ?>>
                <summary class="faq-question">
                    <?php echo htmlspecialchars($faq['question']); ?>
                </summary>
                <div class="faq-answer">
                    <?php echo nl2br(htmlspecialchars($faq['answer']));  ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

   

</div>
