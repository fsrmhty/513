<?php
require_once 'config/session.php';
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Contact TechBuild Pro</h1>
    <p class="page-subtitle">Have questions about a custom build? Need to reschedule a repair? We're here to help!</p>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">📍 Service Area</h3>
        <p style="line-height: 1.7;">
            Currently serving: <strong>Local community (simulated for educational purposes)</strong><br>
            All repairs are on-site or remote — no drop-off required.
        </p>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">📧 Get in Touch</h3>
        <p style="margin-bottom: 1rem;">
            While this is a student project (no live email backend), in a real deployment you could:
        </p>
        <div style="display: grid; gap: 0.5rem;">
            <p>📧 Email us: <strong>support@techbuildpro.local</strong></p>
            <p>📞 Call: <strong>(02) 9999 8888</strong> (Mon–Fri, 9AM–5PM)</p>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">❓ Frequently Asked Questions</h3>
        <div style="display: grid; gap: 1rem;">
            <details style="padding: 1rem; background: var(--gray-50); border-radius: var(--radius);">
                <summary style="font-weight: 600; cursor: pointer;">Do you build PCs for non-gamers?</summary>
                <p style="margin: 1rem 0 0; color: var(--gray-700);">Yes! We offer workstation, home office, and budget builds tailored to your needs — not just gaming.</p>
            </details>
            <details style="padding: 1rem; background: var(--gray-50); border-radius: var(--radius);">
                <summary style="font-weight: 600; cursor: pointer;">How long does a repair take?</summary>
                <p style="margin: 1rem 0 0; color: var(--gray-700);">Most software issues are resolved in under 2 hours. Hardware repairs may require part ordering (3–5 days).</p>
            </details>
            <details style="padding: 1rem; background: var(--gray-50); border-radius: var(--radius);">
                <summary style="font-weight: 600; cursor: pointer;">Can I return a component if I change my mind?</summary>
                <p style="margin: 1rem 0 0; color: var(--gray-700);">Unopened components can be returned within 14 days. Custom builds are non-refundable but covered by 90-day warranty.</p>
            </details>
        </div>
    </div>

<div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
    <h3 style="color: var(--primary); margin-bottom: 1rem;">📝 Share Your Feedback</h3>
    <p style="color: var(--gray-600); margin-bottom: 1.5rem; line-height: 1.6;">
        We'd love to hear from you! Whether you have suggestions, encountered issues, 
        or just want to share your experience, please use our feedback form.
    </p>
    
    <!-- 主要反馈按钮 -->
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <a href="http://hhh.free.nf/WP/feedback form" 
           target="_blank" 
           class="btn btn-primary btn-lg"
           style="padding: 1rem 2rem; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center;">
            <span style="margin-right: 0.5rem; font-size: 1.2rem;">📨</span>
            Open Feedback Form
        </a>
    </div>
    
    <p style="color: var(--gray-500); font-size: 0.875rem; text-align: center; margin-bottom: 1.5rem;">
        <em>Opens in a new tab - Your feedback helps us improve!</em>
    </p>
    
    <!-- 快速反馈选项（可选） -->
    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius); margin-top: 1.5rem;">
        <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Quick Feedback Topics:</h4>
        <div style="display: grid; gap: 0.75rem;">
            <a href="https://forms.gle/YOUR_FORM_LINK?topic=website" 
               target="_blank"
               style="display: block; padding: 0.75rem; background: white; border-radius: var(--radius); 
                      text-decoration: none; color: var(--gray-700); border-left: 4px solid var(--primary);">
                <strong>Website Experience</strong><br>
                <small>Navigation, speed, mobile experience</small>
            </a>
            <a href="https://forms.gle/YOUR_FORM_LINK?topic=products" 
               target="_blank"
               style="display: block; padding: 0.75rem; background: white; border-radius: var(--radius); 
                      text-decoration: none; color: var(--gray-700); border-left: 4px solid var(--success);">
                <strong>Products & Services</strong><br>
                <small>Product selection, pricing, repair services</small>
            </a>
            <a href="https://forms.gle/YOUR_FORM_LINK?topic=support" 
               target="_blank"
               style="display: block; padding: 0.75rem; background: white; border-radius: var(--radius); 
                      text-decoration: none; color: var(--gray-700); border-left: 4px solid var(--warning);">
                <strong>Customer Support</strong><br>
                <small>Response time, helpfulness, communication</small>
            </a>
        </div>
    </div>
</div>

<script>
function handleSubmit(e) {
    e.preventDefault();
    const name = document.getElementById('name').value;
    const resultDiv = document.getElementById('formResult');
    
    resultDiv.innerHTML = `
        <div class="alert alert-success">
            ✅ Thank you, ${name}! In a live system, your message would be sent to our team.
        </div>
    `;
    resultDiv.style.display = 'block';
    document.getElementById('contactForm').reset();
    
    // 滚动到结果
    resultDiv.scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'includes/footer.php'; ?>