<?php
// php -S localhost:3000
if (!session_id())
    session_start();
include_once __DIR__ . "/database/database.php";

// Setting up pagination variables
$limit = 16;
$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$off = ($page * $limit) - $limit;

// Handling search input
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';

// Constructing the base query
$query_base = "SELECT event_id, name, date, banner FROM events WHERE 1=1";

// Adding conditions for search
if ($search_name) {
    $query_base .= " AND name LIKE ?";
}
if ($search_date) {
    $query_base .= " AND date = ?";
}

// Preparing the count query
$total_query = "SELECT COUNT(*) as total FROM ($query_base) as count_query";
$stmt = $dbs->prepare($total_query);

// Binding parameters for count query
if ($search_name && $search_date) {
    $stmt->bind_param("ss", $search_name_param, $search_date);
    $search_name_param = "%" . $search_name . "%";
} elseif ($search_name) {
    $stmt->bind_param("s", $search_name_param);
    $search_name_param = "%" . $search_name . "%";
} elseif ($search_date) {
    $stmt->bind_param("s", $search_date);
}

// Executing the count query
$stmt->execute();
$data = $stmt->get_result();
$total = $data->fetch_array(MYSQLI_ASSOC)['total'];
$stmt->close();

$total_pages = ceil($total / $limit); // Calculate total pages

// Preparing the main query with pagination
$query = $query_base . " ORDER BY event_id DESC LIMIT ? OFFSET ?";
$stmt = $dbs->prepare($query);

// Binding parameters for main query
if ($search_name && $search_date) {
    $stmt->bind_param("ssii", $search_name_param, $search_date, $limit, $off);
} elseif ($search_name) {
    $stmt->bind_param("sii", $search_name_param, $limit, $off);
} elseif ($search_date) {
    $stmt->bind_param("sii", $search_date, $limit, $off);
} else {
    $stmt->bind_param("ii", $limit, $off);
}

// Executing the main query
$stmt->execute();
$data = $stmt->get_result();
$events = $data->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title>Event List - OasisSeek</title>
    <link rel="stylesheet" type="text/css" href="/css/styles.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
   <!-- ======== HEADER ======== -->
   <div class="landing-container">
   
   <?php include_once __DIR__. "/template/navbar.php"; ?>

  <!-- ======== EVENT LIST ======== -->
  <main>
      <h1 class="section-title">Events</h1>

      <!-- Search Form -->
      <form method="GET" action="">
          <input type="text" name="search_name" placeholder="Search by name" value="<?= htmlspecialchars($search_name); ?>">
          <input type="date" name="search_date" placeholder="Search by date" value="<?= htmlspecialchars($search_date); ?>">
          <button type="submit">Search</button>
      </form>

      <div class="event-list">
          <?php foreach ($events as $event): ?>
              <div class="event-card">
                  <img src="/images/events/<?= htmlspecialchars($event['banner']); ?>" alt="<?= htmlspecialchars($event['name']); ?>" class="event-image"/>
                  <div class="event-info">
                      <h2 class="event-name"><?= htmlspecialchars($event['name']); ?></h2>
                      <p class="event-date"><?= htmlspecialchars($event['date']); ?></p>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>

      <!-- Pagination links -->
      <div class="pagination">
          <?php if ($page > 1): ?>
              <a href="?page=<?= $page - 1; ?>&search_name=<?= htmlspecialchars($search_name); ?>&search_date=<?= htmlspecialchars($search_date); ?>">&laquo; Previous</a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="?page=<?= $i; ?>&search_name=<?= htmlspecialchars($search_name); ?>&search_date=<?= htmlspecialchars($search_date); ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?= $i; ?></a>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
              <a href="?page=<?= $page + 1; ?>&search_name=<?= htmlspecialchars($search_name); ?>&search_date=<?= htmlspecialchars($search_date); ?>">Next &raquo;</a>
          <?php endif; ?>
      </div>
  </main>

  <!-- =========== FOOTER =========== -->
  <?php include_once __DIR__ . "/template/footer.php"; ?>

</body>
</html>
