<?php if ($user['role'] !== 'member' || $user['member_status'] !== 'active'): ?><div class="notice-box">An active member account is required to manage your profile.</div><?php else: ?>
<section class="page-heading compact-heading"><p class="eyebrow">Member portal</p><h1>Profile settings</h1><p>Update your contact and department details. Your official staff ID cannot be changed here.</p></section>
<section class="panel form-panel"><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="update_profile">
    <label>Full name<input name="full_name" value="<?= e($user['full_name']) ?>" maxlength="190" required></label>
    <label>Staff ID<input value="<?= e($user['staff_id']) ?>" disabled></label>
    <label>Email address<input value="<?= e($user['email']) ?>" disabled></label>
    <label>Phone number<input name="phone" value="<?= e($user['phone']) ?>" maxlength="40" required></label>
    <label>Department<input name="department" value="<?= e($user['department']) ?>" maxlength="150"></label>
    <button class="button button-primary" type="submit">Save profile</button>
</form></section>
<section class="panel form-panel"><h2>Change password</h2><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="change_password">
    <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
    <label>New password<input type="password" name="new_password" minlength="10" autocomplete="new-password" required><small>At least 10 characters.</small></label>
    <label>Confirm new password<input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></label>
    <button class="button button-primary" type="submit">Change password</button>
</form></section><?php endif; ?>
