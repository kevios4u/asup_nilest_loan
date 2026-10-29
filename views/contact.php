<section class="page-heading contact-hero"><p class="eyebrow">Contact &amp; help</p><h1>Here to help you get started.</h1><p>For help with your membership application, contributions, or account access, please contact the ASUP NILEST scheme administrator through your official scheme communication channel.</p></section>
<section class="support-shell">
  <div class="support-panel-wrap">
    <p class="eyebrow">Support centre</p>
    <h2>Practical guidance for every stage of your membership.</h2>
    <p>Whether you are applying, checking your contribution, or planning your next step, our support resources are designed to keep your account clear, informed, and fully in control.</p>
    <div class="help-tags"><span class="help-label">Available help</span><a href="index.php?page=membership">Application</a><a href="index.php?page=statement">Contribution</a><a href="index.php?page=login">Account access</a></div>
    <div class="support-list"><p class="support-list-label">Need help with?</p>
      <ul>
        <li><ion-icon name="document-text-outline"></ion-icon><a href="index.php?page=register">Membership application</a></li>
        <li><ion-icon name="wallet-outline"></ion-icon><a href="index.php?page=requests">Contribution updates</a></li>
        <li><ion-icon name="lock-closed-outline"></ion-icon><a href="index.php?page=login">Account access</a></li>
        <li><ion-icon name="mail-outline"></ion-icon><a href="#contact-form">Official communication</a></li>
      </ul>
    </div>
  </div>

  <section class="content-grid support-grid">
    <article class="content-card"><span class="feature-icon"><ion-icon name="help-circle-outline" aria-hidden="true"></ion-icon></span><h2>Application help</h2><p>Check that your staff ID and email address are correct. If your application is pending, an administrator must review it before portal features are available.</p></article>
    <article class="content-card"><span class="feature-icon"><ion-icon name="wallet-outline" aria-hidden="true"></ion-icon></span><h2>Contribution questions</h2><p>Your member statement displays posted contributions. Contact the administrator promptly if a monthly salary deduction is not reflected.</p></article>
    <article class="content-card"><span class="feature-icon"><ion-icon name="trending-up-outline" aria-hidden="true"></ion-icon></span><h2>Increase/Decrease of Contribution</h2><p>Contribution amounts may change when your salary, employment status, or approved deduction arrangement changes. We recommend reviewing your member statement regularly and contacting the scheme administrator.</p></article>
    <article class="content-card info-card"><span class="feature-icon"><ion-icon name="cash-outline" aria-hidden="true"></ion-icon></span><h2>Managing Your Savings Contributions</h2><p class="lead">Financial health is rarely a straight line. Life brings unexpected milestones, new opportunities, and occasional financial tight spots. Because of this, having the flexibility to adjust your financial commitments—such as your monthly contributions to a savings and credit scheme—is essential for maintaining long-term financial wellness.</p><div class="info-block"><h3>When life is good: increasing your contribution</h3><p>When your income grows, your business flourishes, or you find yourself with extra breathing room in your monthly budget, stepping up your contributions is a powerful way to accelerate your goals.</p><ul><li><strong>Build a stronger financial cushion:</strong> Higher contributions directly translate to faster savings growth, giving you a robust safety net for emergencies.</li><li><strong>Enhance borrowing power:</strong> In schemes like the <a href="http://localhost/asup_nilest_loan/index.php">ASUP NILEST Savings &amp; Credit Scheme</a>, your savings often dictate your eligibility or capacity for low-interest loans. A higher contribution profile can strengthen your standing.</li><li><strong>Cultivate disciplined wealth:</strong> Automatically routing extra funds into savings prevents lifestyle inflation, turning temporary windfalls or raises into long-term assets.</li></ul></div><div class="info-block"><h3>When life is tight: decreasing your contribution</h3><p>Financial bumps are a normal part of life. Whether you are facing unexpected expenses, a temporary dip in income, or shifts in your employment status, reducing your monthly deduction can provide immediate relief.</p><ul><li><strong>Protect your cash flow:</strong> Lowering your contribution frees up immediate cash to cover essential day-to-day living expenses without falling into debt.</li><li><strong>Stay active and engaged:</strong> Rather than defaulting or dropping out of a cooperative or savings program entirely during tough times, scaling back lets you stay compliant and maintain your membership standing.</li><li><strong>A temporary pivot:</strong> Remember that a reduction does not have to be permanent. Once your financial situation stabilizes, you can step your contributions back up.</li></ul></div><div class="info-block"><h3>How to make adjustments</h3><p>Contribution amounts typically change when your salary, personal financial goals, or approved deduction arrangements shift. To keep your records accurate:</p><ol><li><strong>Review regularly:</strong> Check your member statements routinely to ensure your deductions align with what you expect.</li><li><strong>Communicate promptly:</strong> If you need to adjust your contribution levels or notice any discrepancies, reach out directly to the scheme administrator through your official communication channels, as outlined on the <a href="index.php?page=contact">Contact &amp; help</a> page.</li></ol></div></article>
  </section>

  <section class="contact-form-section" id="contact-form">
    <div class="panel form-panel contact-form-panel">
      <div class="section-heading"><div><p class="eyebrow">Send a message</p><h2>We’re ready to help</h2></div></div>
      <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="contact_submit">
        <div class="form-row">
          <label>Full name<input type="text" name="full_name" maxlength="190" required></label>
          <label>Email address<input type="email" name="email" maxlength="190" required></label>
        </div>
        <div class="form-row">
          <label>Phone number<input type="tel" name="phone" maxlength="40" placeholder="Optional"></label>
          <label>Subject<input type="text" name="subject" maxlength="190" required></label>
        </div>
        <label>How can we help?<textarea name="message" rows="6" maxlength="4000" minlength="10" required placeholder="Tell us about your concern, question, or request."></textarea></label>
        <button class="button button-primary" type="submit">Send message</button>
      </form>
    </div>

    <aside class="panel contact-meta-panel">
      <p class="eyebrow">Direct support</p>
      <h3>Need help?</h3>
      <p>Contact the Scheme Secretariat or visit the <a href="index.php?page=home">portal</a> for more information.</p>
      <ul>
        <li><strong>Phone</strong><span>080 XXXX XXXXX</span></li>
        <li><strong>Email</strong><span><a href="mailto:info@asupnilestloanscheme">info@asupnilestloanscheme</a></span></li>
        <li><strong>Response time</strong><span>Within 1–2 working days</span></li>
        <li><strong>Primary topics</strong><span>Applications, increase contribution, reduce contribution, access, and account updates</span></li>
        <li><strong>Official channels</strong><span>Use the scheme’s verified contact instructions provided by the administrator</span></li>
      </ul>
    </aside>
  </section>
</section>