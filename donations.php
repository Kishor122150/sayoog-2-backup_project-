<?php
require_once 'config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

$donations = get_available_donations($pdo);
?>
<?php
$page_title = 'Food Listings | Sayog';
$active_page = 'donations';
require_once 'header.php';
?>
    <style>
    /* Nepal Map Pin System */
    .map-toolbar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px 16px; margin-bottom: 16px; box-shadow: var(--shadow-sm); }
    .map-cluster-icon { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #fff; border: 3px solid rgba(255,255,255,0.6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
    .map-cluster-icon-small { width:36px;height:36px;font-size:12px; } .map-cluster-icon-medium { width:44px;height:44px;font-size:14px; } .map-cluster-icon-large { width:52px;height:52px;font-size:16px; }
    .donor-marker-icon { background:#059669;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:16px; }
    .user-location-pulse { width:14px;height:14px;background:#3b82f6;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 4px rgba(59,130,246,0.3),0 2px 8px rgba(0,0,0,0.3);animation:userPulse 2s ease-in-out infinite; }
    @keyframes userPulse { 0%,100%{box-shadow:0 0 0 4px rgba(59,130,246,0.3),0 2px 8px rgba(0,0,0,0.3)} 50%{box-shadow:0 0 0 8px rgba(59,130,246,0.1),0 2px 8px rgba(0,0,0,0.3)} }
    input[type=range]::-webkit-slider-runnable-track { height:4px;background:#d1d5db;border-radius:4px; }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance:none;height:16px;width:16px;border-radius:50%;background:#059669;margin-top:-6px;cursor:pointer;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.2); }
    </style>


    <main class="site-main">
        <section class="section-block">
            <div class="section-heading">
                <h1 data-i18n="donations.heading">Available Food Listings</h1>
                <p data-i18n="donations.description">Browse recent food donations that are ready for pickup or request.</p>
            </div>

            <?php if (!empty($donations)): ?>
                                <!-- Enhanced Search Toolbar - Nepal Map -->
                <div class="map-toolbar">
                    <div class="map-toolbar-row" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px;">
                        <div class="home-search" style="flex:1;min-width:160px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchFoodName" class="form-control" placeholder="Food name..." style="padding-left:36px;">
                        </div>
                        <div class="home-search" style="flex:1;min-width:160px;">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" id="searchLocation" class="form-control" placeholder="Address..." style="padding-left:36px;">
                        </div>
                        <div class="home-search" style="flex:0 1 auto;min-width:130px;">
                            <i class="fa-solid fa-city"></i>
                            <select id="cityFilter" class="form-control" style="padding-left:36px;">
                                <option value="">All Cities</option>
                                <option value="Kathmandu">Kathmandu</option>
                                <option value="Lalitpur">Lalitpur</option>
                                <option value="Bhaktapur">Bhaktapur</option>
                                <option value="Pokhara">Pokhara</option>
                                <option value="Bharatpur">Bharatpur</option>
                                <option value="Birgunj">Birgunj</option>
                                <option value="Biratnagar">Biratnagar</option>
                                <option value="Janakpur">Janakpur</option>
                                <option value="Butwal">Butwal</option>
                                <option value="Dharan">Dharan</option>
                                <option value="Nepalgunj">Nepalgunj</option>
                                <option value="Chitwan">Chitwan</option>
                                <option value="Hetauda">Hetauda</option>
                                <option value="Dhangadhi">Dhangadhi</option>
                                <option value="Itahari">Itahari</option>
                            </select>
                        </div>
                        <div class="filter-chips">
                            <button class="filter-chip active" data-filter="all" onclick="window.filterDonations('all')">All</button>
                            <button class="filter-chip" data-filter="nearby" onclick="window.filterDonations('nearby')">Nearby</button>
                        </div>
                    </div>
                    <div class="map-toolbar-row" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);">
                            <i class="fa-solid fa-ruler"></i>
                            <span id="radiusLabel">Radius: 10 km</span>
                            <input type="range" id="radiusFilter" min="1" max="50" value="10" step="1" style="width:100px;accent-color:#059669;" oninput="document.getElementById('radiusLabel').textContent='Radius: '+this.value+' km';window.filterDonations(document.querySelector('.filter-chip.active')?.dataset?.filter||'all');">
                        </div>
                        <span id="mapResultsCount" class="badge badge-success" style="font-size:12px;"><?php echo count($donations); ?> donations</span>
                    </div>

                <!-- Interactive Map Section -->
                <div class="map-section">
                    <div class="map-section-header">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <h3>Donation Locations <span style="font-weight:400;color:var(--text-muted);font-size:13px;">— Click markers for details &amp; directions</span></h3>
                    </div>
                    <div class="map-container" id="donationsMap"></div>
                </div>
            <?php endif; ?>

            <?php if (empty($donations)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bowl-food"></i>
                    <h3>No active food listings right now.</h3>
                    <p>Donors can create donation entries from their dashboard.</p>
                </div>
            <?php else: ?>
                <div id="donationsGrid" class="product-grid">
                    <?php foreach ($donations as $d): ?>
                        <article class="product-card" data-donation-id="<?php echo $d['id']; ?>" data-food-item="<?php echo htmlspecialchars(strtolower($d['food_item'])); ?>" data-pickup-address="<?php echo htmlspecialchars(strtolower($d['pickup_address'])); ?>" data-donor-name="<?php echo htmlspecialchars($d['donor_name']); ?>">
                            <div class="product-card-image">
                                <?php if (!empty($d['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($d['image_path']); ?>" alt="<?php echo htmlspecialchars($d['food_item']); ?>">
                                <?php else: ?>
                                    <div class="product-placeholder"><i class="fa-solid fa-bowl-food"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($d['food_item']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($d['description'] ?? '', 0, 140)); ?><?php echo strlen($d['description'] ?? '') > 140 ? '...' : ''; ?></p>
                                <div class="product-card-meta">
                                    <span>Quantity: <?php echo htmlspecialchars($d['quantity']); ?></span>
                                    <span> | <span class="countdown-badge" data-expiry="<?php echo $d['expiry_time']; ?>">⏳ Loading...</span></span>
                                </div>
                                <div style="margin-top:8px; font-size:13px; color:#666;">
                                    Donor: <?php echo htmlspecialchars($d['donor_name']); ?> | Pickup: <?php echo htmlspecialchars($d['pickup_address']); ?>
                                    <span class="donation-distance"><i class="fa-solid fa-location-dot"></i> Locating...</span>
                                </div>
                                <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    <a href="donation.php?id=<?php echo $d['id']; ?>" class="btn btn-primary">View Details</a>
                                    <a href="login.php?redirect=donation.php?id=<?php echo $d['id']; ?>" class="btn btn-outline">Request Pickup</a>
                                    <?php if (!empty($d['phone'])): ?>
                                        <?php
                                        $wa_msg = 'Hello ' . $d['donor_name'] . ', I am interested in your food donation: ' . $d['food_item'] . ' on Sayog.';
                                        echo '<a href="' . get_whatsapp_link($d['phone'], $wa_msg) . '" target="_blank" class="btn btn-whatsapp btn-whatsapp-sm"><i class="fa-brands fa-whatsapp"></i> Chat</a>';
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php if (!empty($donations)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var donations = [
            <?php foreach ($donations as $d): ?>
            {
                id: <?php echo $d['id']; ?>,
                food_item: <?php echo json_encode($d['food_item']); ?>,
                address: <?php echo json_encode($d['pickup_address']); ?>,
                pickup_address: <?php echo json_encode($d['pickup_address']); ?>,
                latitude: <?php echo !empty($d['latitude']) ? $d['latitude'] : 'null'; ?>,
                longitude: <?php echo !empty($d['longitude']) ? $d['longitude'] : 'null'; ?>,
                city: <?php echo !empty($d['city']) ? json_encode($d['city']) : 'null'; ?>
            },
            <?php endforeach; ?>
        ];
        if (typeof window.initDonationsMap === 'function') {
            window.initDonationsMap('donationsMap', donations);
        }
    });
    </script>
    <?php endif; ?>

    <?php require_once 'footer.php'; ?>
