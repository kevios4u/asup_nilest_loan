<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">ASUP NILEST · A community that saves together</p>
        <h1>Plan for tomorrow.<br><span>Grow together.</span></h1>
        <p class="hero-lede">A transparent savings and credit scheme for ASUP NILEST members. Track your contributions, make a plan, and access member support in one place.</p>
        <div class="button-row"><a class="button button-primary" href="index.php?page=register">Apply for membership <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a><a class="button button-outline" href="index.php?page=about">Discover the scheme</a></div>
        <div class="hero-note"><span class="note-icon"><ion-icon name="checkmark-outline" aria-hidden="true"></ion-icon></span> Membership applications are reviewed by the scheme administrator.</div>
    </div>
    <div class="hero-art" aria-label="Savings and financial growth illustration">
        <div class="art-circle"></div><div class="art-card art-card-top"><span>YOUR SAVINGS</span><strong>Growing steadily</strong><div class="mini-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
        <div class="art-card art-card-main"><div class="art-icon"><ion-icon name="wallet-outline" aria-hidden="true"></ion-icon></div><span>Small steps, shared progress</span><strong>Save with confidence.</strong><div class="art-line"></div></div><div class="art-spark"><ion-icon name="sparkles-outline" aria-hidden="true"></ion-icon></div>
    </div>
</section>
<section class="registration-callout">
    <div>
        <p class="eyebrow">For members and prospective members</p>
        <h2>Registration and membership updates</h2>
        <p>Online registration opens <strong>1 November 2026</strong> and closes <strong>30 November 2026</strong>. New and existing members can register or update their membership details through the official portal.</p>
        <a class="button button-primary" href="<?= OFFICIAL_PORTAL_URL ?>" target="_blank" rel="noopener noreferrer">Visit the official portal <ion-icon name="open-outline" aria-hidden="true"></ion-icon></a>
    </div>
    <div class="registration-dates"><strong>1–30</strong><span>November 2026</span></div>
</section>
<section class="feature-band">
    <article><span class="feature-icon"><ion-icon name="receipt-outline" aria-hidden="true"></ion-icon></span><div><h3>Clear records</h3><p>Member statements backed by an auditable transaction ledger.</p></div></article>
    <article><span class="feature-icon"><ion-icon name="trending-up-outline" aria-hidden="true"></ion-icon></span><div><h3>Member-led</h3><p>Manage requests, contribution changes, and loan applications.</p></div></article>
    <article><span class="feature-icon"><ion-icon name="shield-checkmark-outline" aria-hidden="true"></ion-icon></span><div><h3>Accountable</h3><p>Administrator reviews and financial actions are recorded.</p></div></article>
</section>
<section class="section-block">
    <div class="section-heading"><div><p class="eyebrow">The member journey</p><h2>A simple way to save with purpose</h2></div><a class="text-link" href="index.php?page=membership">Membership details →</a></div>
    <div class="steps-grid"><article><span>01</span><h3>Apply to join</h3><p>Submit your staff details and proposed monthly contribution.</p></article><article><span>02</span><h3>Build your savings</h3><p>Your posted monthly contributions appear in your personal statement.</p></article><article><span>03</span><h3>Make a request</h3><p>Submit eligible savings or credit requests for administrator review.</p></article></div>
</section>
<?php if ($announcements): ?>
<section class="section-block announcements-block"><div class="section-heading"><div><p class="eyebrow">Stay informed</p><h2>Latest announcements</h2></div></div>
    <div class="announcement-grid"><?php foreach ($announcements as $item): ?><article class="announcement-card"><time><?= e(date('M j, Y', strtotime($item['created_at']))) ?></time><h3><?= e($item['title']) ?></h3><p><?= nl2br(e($item['body'])) ?></p></article><?php endforeach; ?></div>
</section>
<?php endif; ?>
