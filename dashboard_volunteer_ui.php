<?php
/**
 * dashboard_volunteer_ui.php
 * Include file for volunteer delivery UI components
 * Included from dashboard.php for the track-request and volunteer pages.
 * Provides: delivery method selection, available deliveries list, active deliveries with status actions.
 */

// ──────────────────────────────────────────────────────────
// DELIVERY METHOD SELECTION MODAL (shown on track-request page
// when a request is 'approved' but delivery_method is not set)
// ──────────────────────────────────────────────────────────
function render_delivery_method_modal($pdo, $request_id, $donation_id, $food_item) {
    ?>
    <div class="delivery-method-banner" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:2px solid #a7f3d0;border-radius:16px;padding:28px 24px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#059669,#10b981);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-truck-fast" style="color:#fff;font-size:22px;"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <h3 style="font-size:18px;font-weight:700;margin:0 0 6px;color:#0f172a;">Your request for "<?php echo htmlspecialchars($food_item); ?>" was approved! 🎉</h3>
                <p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.5;">
                    Choose how you'd like to receive this food donation.
                </p>
                <form action="dashboard.php?page=track-request" method="POST" style="display:flex;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="select_delivery_method">
                    <input type="hidden" name="donation_id" value="<?php echo (int)$donation_id; ?>">
                    <input type="hidden" name="request_id" value="<?php echo (int)$request_id; ?>">
                    <button type="submit" name="delivery_method" value="volunteer" class="btn btn-primary" style="padding:12px 24px;font-size:14px;flex:1;min-width:160px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fa-solid fa-hand-holding-heart"></i> Volunteer Delivery
                        <span style="font-size:11px;opacity:0.8;display:block;">A volunteer delivers to you</span>
                    </button>
                    <button type="submit" name="delivery_method" value="self_pickup" class="btn btn-outline" style="padding:12px 24px;font-size:14px;flex:1;min-width:160px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fa-solid fa-person-walking"></i> Self Pickup
                        <span style="font-size:11px;opacity:0.8;display:block;">Collect from donor directly</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// ──────────────────────────────────────────────────────────
// AVAILABLE DELIVERIES LIST (for volunteer hub)
// ──────────────────────────────────────────────────────────
function render_available_deliveries($pdo, $deliveries) {
    if (empty($deliveries)): ?>
        <div class="empty-state" style="padding:40px 20px;text-align:center;">
            <i class="fa-solid fa-truck" style="font-size:40px;color:#9ca3af;margin-bottom:12px;"></i>
            <h3 style="margin:0 0 6px;font-size:16px;font-weight:700;color:var(--text-primary);">No deliveries available</h3>
            <p style="margin:0;color:var(--text-muted);font-size:14px;">Check back later or set your status to "Available" to receive requests.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
            <?php foreach ($deliveries as $del): ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:all 0.2s;">
                <div style="background:linear-gradient(135deg,#059669,#047857);padding:14px 18px;color:#fff;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo htmlspecialchars($del['food_item']); ?></h3>
                        <span style="background:rgba(255,255,255,0.2);padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;">
                            <i class="fa-solid fa-box"></i> <?php echo htmlspecialchars($del['quantity']); ?>
                        </span>
                    </div>
                </div>
                <div style="padding:16px 18px;">
                    <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;color:var(--text-secondary);margin-bottom:14px;">
                        <span><i class="fa-solid fa-user" style="width:18px;color:#059669;"></i> Donor: <?php echo htmlspecialchars($del['donor_name']); ?></span>
                        <span><i class="fa-solid fa-location-dot" style="width:18px;color:#059669;"></i> <?php echo htmlspecialchars($del['pickup_address']); ?></span>
                        <?php if (!empty($del['city'])): ?>
                        <span><i class="fa-solid fa-city" style="width:18px;color:#059669;"></i> <?php echo htmlspecialchars($del['city']); ?></span>
                        <?php endif; ?>
                        <span><i class="fa-solid fa-clock" style="width:18px;color:#059669;"></i> Consume before: <?php echo date('M d, Y h:i A', strtotime($del['expiry_time'])); ?></span>
                        <?php if (!empty($del['request_message'])): ?>
                        <span><i class="fa-solid fa-comment" style="width:18px;color:#059669;"></i> Request: <?php echo htmlspecialchars($del['request_message']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($del['_proximity_score'])): ?>
                        <span style="color:#059669;font-weight:600;"><i class="fa-solid fa-location-crosshairs"></i> Near your area</span>
                        <?php endif; ?>
                    </div>
                    <form action="dashboard.php?page=volunteer" method="POST">
                        <input type="hidden" name="action" value="accept_volunteer_delivery">
                        <input type="hidden" name="delivery_id" value="<?php echo (int)$del['id']; ?>">
                        <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;">
                            <i class="fa-solid fa-hand-holding-heart"></i> Accept Delivery
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}

// ──────────────────────────────────────────────────────────
// ACTIVE DELIVERIES (for volunteer hub - with status actions)
// ──────────────────────────────────────────────────────────
function render_active_deliveries($pdo, $deliveries) {
    if (empty($deliveries)): ?>
        <div class="empty-state" style="padding:40px 20px;text-align:center;">
            <i class="fa-solid fa-truck-fast" style="font-size:40px;color:#9ca3af;margin-bottom:12px;"></i>
            <h3 style="margin:0 0 6px;font-size:16px;font-weight:700;color:var(--text-primary);">No active deliveries</h3>
            <p style="margin:0;color:var(--text-muted);font-size:14px;">Accept a delivery from the available list above to get started.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
            <?php foreach ($deliveries as $del):
                $deliveryStatus = $del['status'];
                $statusColors = ['accepted'=>'#3b82f6','picked_up'=>'#f59e0b','in_transit'=>'#8b5cf6'];
                $statusIcons = ['accepted'=>'fa-check-circle','picked_up'=>'fa-box-open','in_transit'=>'fa-truck'];
                $nextActions = ['accepted'=>[['picked_up','Mark as Picked Up','fa-box-open']],
                                'picked_up'=>[['in_transit','Mark as In Transit','fa-truck']],
                                'in_transit'=>[['delivered','Mark as Delivered','fa-check-double']]];
                $nextSteps = $nextActions[$deliveryStatus] ?? [];
            ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;border-left:4px solid <?php echo $statusColors[$deliveryStatus] ?? '#6b7280'; ?>;">
                <div style="padding:16px 18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo htmlspecialchars($del['food_item']); ?></h3>
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:<?php echo $statusColors[$deliveryStatus] ?? '#e5e7eb'; ?>20;color:<?php echo $statusColors[$deliveryStatus] ?? '#6b7280'; ?>;">
                            <i class="fa-solid <?php echo $statusIcons[$deliveryStatus] ?? 'fa-circle'; ?>"></i>
                            <?php echo ucfirst(str_replace('_',' ',$deliveryStatus)); ?>
                        </span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--text-secondary);margin-bottom:12px;">
                        <span><i class="fa-solid fa-user" style="width:16px;color:#059669;"></i> Donor: <?php echo htmlspecialchars($del['donor_name'] ?? 'N/A'); ?> <?php echo !empty($del['donor_contact']) ? '('.htmlspecialchars($del['donor_contact']).')' : ''; ?></span>
                        <span><i class="fa-solid fa-user" style="width:16px;color:#059669;"></i> Consumer: <?php echo htmlspecialchars($del['consumer_name'] ?? 'N/A'); ?> <?php echo !empty($del['consumer_phone']) ? '('.htmlspecialchars($del['consumer_phone']).')' : ''; ?></span>
                        <span><i class="fa-solid fa-location-dot" style="width:16px;color:#059669;"></i> Pickup: <?php echo htmlspecialchars($del['pickup_address'] ?? 'N/A'); ?></span>
                        <?php if (!empty($del['consumer_address'])): ?>
                        <span><i class="fa-solid fa-location-dot" style="width:16px;color:#f59e0b;"></i> Deliver to: <?php echo htmlspecialchars($del['consumer_address']); ?></span>
                        <?php endif; ?>
                        <span><i class="fa-solid fa-boxes-stacked" style="width:16px;color:#059669;"></i> Qty: <?php echo htmlspecialchars($del['quantity'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($nextSteps)): ?>
                        <?php foreach ($nextSteps as $step): ?>
                        <form action="dashboard.php?page=volunteer" method="POST" style="margin-bottom:6px;">
                            <input type="hidden" name="action" value="update_volunteer_delivery_status">
                            <input type="hidden" name="delivery_id" value="<?php echo (int)$del['id']; ?>">
                            <input type="hidden" name="delivery_status" value="<?php echo $step[0]; ?>">
                            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;background:<?php echo $step[0]==='delivered' ? 'linear-gradient(135deg,#059669,#047857)' : 'var(--primary)'; ?>;">
                                <i class="fa-solid <?php echo $step[2]; ?>"></i> <?php echo $step[1]; ?>
                            </button>
                        </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}

// ──────────────────────────────────────────────────────────
// DELIVERY HISTORY (for volunteer hub)
// ──────────────────────────────────────────────────────────
function render_delivery_history($pdo, $history) {
    if (empty($history)): ?>
        <p style="font-size:13px;color:var(--text-muted);margin:0;">No completed deliveries yet.</p>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($history as $h): 
                // Get rating for this delivery if it's delivered
                $ratingDisplay = '';
                if ($h['status'] === 'delivered' && isset($h['donation_id'])) {
                    $rtStmt = $pdo->prepare("SELECT rating_volunteer, review_volunteer FROM ratings WHERE donation_id = ?");
                    $rtStmt->execute([$h['donation_id']]);
                    $rt = $rtStmt->fetch();
                    if ($rt && !empty($rt['rating_volunteer'])) {
                        $starsHtml = '';
                        for ($i = 0; $i < 5; $i++) {
                            $starsHtml .= '<i class="fa-solid fa-star" style="color:' . ($i < (int)$rt['rating_volunteer'] ? '#f59e0b' : '#d1d5db') . ';font-size:10px;"></i>';
                        }
                        $ratingDisplay = '<span style="margin-left:6px;">' . $starsHtml . '</span>';
                    }
                }
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:10px;font-size:13px;transition:all 0.2s;" onmouseover="this.style.borderColor='#a7f3d0';this.style.boxShadow='0 2px 8px rgba(5,150,105,0.08)'" onmouseout="this.style.borderColor='';this.style.boxShadow=''">
                <div style="display:flex;flex-direction:column;gap:4px;">
                    <div>
                        <strong><?php echo htmlspecialchars($h['food_item'] ?? 'N/A'); ?></strong>
                        <span style="color:var(--text-muted);margin-left:8px;">→ <?php echo htmlspecialchars($h['consumer_name'] ?? 'N/A'); ?></span>
                        <?php echo $ratingDisplay; ?>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;">
                        <span><i class="fa-regular fa-calendar"></i> <?php echo isset($h['updated_at']) ? date('d M Y', strtotime($h['updated_at'])) : 'N/A'; ?></span>
                        <?php if (!empty($h['donor_name'])): ?>
                            <span style="margin-left:12px;"><i class="fa-solid fa-user"></i> Donor: <?php echo htmlspecialchars($h['donor_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:999px;font-size:11px;font-weight:600;background:<?php echo $h['status']==='delivered'?'rgba(5,150,105,0.1)':'rgba(239,68,68,0.1)'; ?>;color:<?php echo $h['status']==='delivered'?'#059669':'#ef4444'; ?>;">
                    <i class="fa-solid <?php echo $h['status']==='delivered'?'fa-check-circle':'fa-times-circle'; ?>"></i>
                    <?php echo $h['status']==='delivered' ? 'Delivered' : 'Cancelled'; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}
