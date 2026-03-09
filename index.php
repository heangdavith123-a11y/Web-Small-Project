<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Education Book Shop</title>
    <link rel="stylesheet" href="style.css">
    <!-- Add Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: Login.php");
        exit;
    }

    include 'ConnDB.php';

    // --- 1. Fetch Summary Stats ---
    
    // Total Books
    $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE is_deleted = 0");
    $total_books = $result->fetch_assoc()['count'];

    // Total Sales (All Time)
    $result = $conn->query("SELECT SUM(total_amount) as total FROM sales");
    $row = $result->fetch_assoc();
    $total_sales = $row['total'] ? $row['total'] : 0;

    // Sales Today
    $result = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $row = $result->fetch_assoc();
    $sales_today = $row['total'] ? $row['total'] : 0;

    // --- 2. Fetch Chart Data ---

    // Helper function to fill missing time slots with 0
    function getChartData($conn, $interval_sql, $format_db, $format_php, $count, $step) {
        $data = [];
        $labels = [];
        
        // Initialize array with 0s for the past $count periods
        for ($i = $count - 1; $i >= 0; $i--) {
            if ($step == 'hour') {
                $key = date($format_php, strtotime("-$i hours"));
            } else {
                $key = date($format_php, strtotime("-$i days"));
            }
            $data[$key] = 0;
        }

        // Fetch real data
        $query = "SELECT DATE_FORMAT(sale_date, '$format_db') as label, SUM(total_amount) as total 
                  FROM sales 
                  WHERE sale_date >= $interval_sql 
                  GROUP BY label";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            if (isset($data[$row['label']])) {
                $data[$row['label']] = (float)$row['total'];
            }
        }

        return [
            'labels' => array_keys($data),
            'data' => array_values($data)
        ];
    }

    // Data for 24 Hours
    $chart24h = getChartData($conn, "NOW() - INTERVAL 24 HOUR", '%H:00', 'H:00', 24, 'hour');

    // Data for 3 Days
    $chart3d = getChartData($conn, "CURDATE() - INTERVAL 2 DAY", '%Y-%m-%d', 'Y-m-d', 3, 'day');

    // Data for Week (7 Days)
    $chartWeek = getChartData($conn, "CURDATE() - INTERVAL 6 DAY", '%Y-%m-%d', 'Y-m-d', 7, 'day');

    // --- 3. Fetch Top Selling Books ---
    $top_books_query = "
        SELECT b.title, SUM(sd.qty) as total_sold, SUM(sd.amount) as total_revenue
        FROM salse_details sd
        JOIN books b ON sd.book_id = b.book_id
        GROUP BY sd.book_id
        ORDER BY total_sold DESC
        LIMIT 5
    ";
    $top_books_result = $conn->query($top_books_query);
    ?>
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Dashboard Overview</h1>
                <p style="color: #6c757d; margin: 5px 0 0 0;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.9em; color: #6c757d;"><?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <div class="dashboard-layout">
            <div class="sidebar">
                <h2 style="margin-top: 0; margin-bottom: 20px;">Quick Actions</h2>
                <div class="dashboard-menu">
                    <a href="add_book.php" class="dashboard-item">
                        <span>&#43;<br>Add New Book</span>
                    </a>
                    <a href="inventory.php" class="dashboard-item">
                        <span>&#128218;<br>Book Inventory</span>
                    </a>
                    <a href="sales_history.php" class="dashboard-item">
                        <span>&#128179;<br>Sales Management</span>
                    </a>
                    <a href="logout.php" class="dashboard-item logout">
                        <span>&#10162;<br>Logout</span>
                    </a>
                </div>
            </div>

            <div class="main-content">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #4e73df;">
                <div class="stat-info">
                    <h3>Total Inventory</h3>
                    <p class="value"><?php echo number_format($total_books); ?></p>
                </div>
                <div class="stat-icon" style="color: #4e73df;">&#128218;</div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid #1cc88a;">
                <div class="stat-info">
                    <h3>Sales Today</h3>
                    <p class="value">$<?php echo number_format($sales_today, 2); ?></p>
                </div>
                <div class="stat-icon" style="color: #1cc88a;">&#128178;</div>
            </div>

            <div class="stat-card" style="border-left: 4px solid #36b9cc;">
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <p class="value">$<?php echo number_format($total_sales, 2); ?></p>
                </div>
                <div class="stat-icon" style="color: #36b9cc;">&#128200;</div>
            </div>
        </div>

        <!-- Sales Graph Section -->
        <div class="chart-section">
            <div class="chart-header">
                <h2 style="margin: 0; font-size: 1.25rem;">Sales Overview</h2>
                <div class="btn-group">
                    <button onclick="updateChart('24h')" class="btn btn-sm btn-secondary" id="btn-24h">24 Hours</button>
                    <button onclick="updateChart('3d')" class="btn btn-sm btn-secondary" id="btn-3d">3 Days</button>
                    <button onclick="updateChart('week')" class="btn btn-sm btn-primary" id="btn-week">This Week</button>
                </div>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top Selling Books Section -->
        <div class="chart-section">
            <div class="chart-header">
                <h2 style="margin: 0; font-size: 1.25rem;">Top Selling Books</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Total Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_books_result && $top_books_result->num_rows > 0): ?>
                        <?php while($book = $top_books_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo $book['total_sold']; ?></td>
                                <td>$<?php echo number_format($book['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center;">No sales data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data from PHP
        const data24h = <?php echo json_encode($chart24h); ?>;
        const data3d = <?php echo json_encode($chart3d); ?>;
        const dataWeek = <?php echo json_encode($chartWeek); ?>;

        const ctx = document.getElementById('salesChart').getContext('2d');
        let salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dataWeek.labels,
                datasets: [{
                    label: 'Sales Amount ($)',
                    data: dataWeek.data,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2] } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        function updateChart(period) {
            // Reset buttons
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            document.getElementById('btn-' + period).classList.remove('btn-secondary');
            document.getElementById('btn-' + period).classList.add('btn-primary');

            // Update Data
            let newData;
            if (period === '24h') newData = data24h;
            else if (period === '3d') newData = data3d;
            else newData = dataWeek;

            salesChart.data.labels = newData.labels;
            salesChart.data.datasets[0].data = newData.data;
            salesChart.update();
        }
    </script>
</body>
</html>