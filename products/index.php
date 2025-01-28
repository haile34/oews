<style>
    .product-img-holder {
        width: 100%;
        height: 15em;
        overflow: hidden;
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center center;
        transition: all .3s ease-in-out;
    }

    .product-item:hover .product-img {
        transform: scale(1.2)
    }
</style>

<?php 
// Page title and description logic
$page_title = "Our Available Products";
$page_description = "";
$cat_where = "";

// Check if a category is selected
if(isset($_GET['cid']) && is_numeric($_GET['cid'])){
    $category_qry = $conn->query("SELECT * FROM `category_list` WHERE `id` = '{$_GET['cid']}' AND `status` = 1 AND `delete_flag` = 0");
    if($category_qry->num_rows > 0){
        $cat_result = $category_qry->fetch_assoc();
        $page_title = $cat_result['name'];
        $page_description = $cat_result['description'];
        $cat_where = " AND `category_id` = '{$cat_result['id']}' ";
    }
}

// Initialize variables for filters
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? $_GET['max_price'] : null;
$brand = isset($_GET['brand']) && !empty($_GET['brand']) ? $_GET['brand'] : null;

// Build the SQL query with the selected filters
$query = "SELECT *, 
    (COALESCE((SELECT SUM(quantity) FROM `stock_list` WHERE product_id = product_list.id), 0) - 
    COALESCE((SELECT SUM(quantity) FROM `order_items` WHERE product_id = product_list.id), 0)) 
    AS `available` 
    FROM `product_list` 
    WHERE (COALESCE((SELECT SUM(quantity) FROM `stock_list` WHERE product_id = product_list.id), 0) - 
    COALESCE((SELECT SUM(quantity) FROM `order_items` WHERE product_id = product_list.id), 0)) > 0 {$cat_where}";

// Apply price filter
if (!empty($min_price) && !empty($max_price)) {
    $query .= " AND price BETWEEN {$min_price} AND {$max_price} ";
} elseif (!empty($min_price)) {
    $query .= " AND price >= {$min_price} ";
} elseif (!empty($max_price)) {
    $query .= " AND price <= {$max_price} ";
}

// Apply brand filter
if (!empty($brand)) {
    $query .= " AND brand = '{$brand}' ";
}

// Execute the query
$qry = $conn->query($query);
?>

<section class="py-3">
    <div class="container">
        <div class="content bg-gradient-dark py-5 px-3">
            <h4 class=""><?= $page_title ?></h4>
            <?php if(!empty($page_description)): ?>
                <hr>
                <p class="m-0"><small><em><?= html_entity_decode($page_description) ?></em></small></p>
            <?php endif; ?>
        </div>

        <!-- Filter Form -->
        <div class="filter-form my-4">
        <form action="" method="GET">
    <input type="hidden" name="cid" value="<?= isset($_GET['cid']) ? $_GET['cid'] : '' ?>">
    <div class="row">
        <!-- Price Filter -->
        <div class="col-md-4">
            <label for="min_price">Min Price</label>
            <input type="number" class="form-control" name="min_price" value="<?= isset($_GET['min_price']) ? $_GET['min_price'] : '' ?>">
        </div>
        <div class="col-md-4">
            <label for="max_price">Max Price</label>
            <input type="number" class="form-control" name="max_price" value="<?= isset($_GET['max_price']) ? $_GET['max_price'] : '' ?>">
        </div>

        <!-- Brand Filter -->
        <div class="col-md-4">
            <label for="brand">Brand</label>
            <select class="form-control" name="brand">
                <option value="">All Brands</option>
                <option value="Brand1" <?= isset($_GET['brand']) && $_GET['brand'] == 'Brand1' ? 'selected' : '' ?>>Brand1</option>
                <option value="Brand2" <?= isset($_GET['brand']) && $_GET['brand'] == 'Brand2' ? 'selected' : '' ?>>Brand2</option>
                <!-- Add more brands dynamically as needed -->
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Apply Filters</button>
</form>

        </div>

        <!-- Product Display Section -->
        <div class="row mt-n3 justify-content-center">
            <div class="col-lg-10 col-md-11 col-sm-11 col-sm-11">
                <div class="card card-outline rounded-0">
                    <div class="card-body">
                        <div class="row row-cols-xl-4 row-md-6 col-sm-12 col-xs-12 gy-2 gx-2">
                            <?php 
                            // Loop through filtered products and display them
                            while($row = $qry->fetch_assoc()):
                            ?>
                            <div class="col">
                                <a class="card rounded-0 shadow product-item text-decoration-none text-reset h-100" href="./?p=products/view_product&id=<?= $row['id'] ?>">
                                    <div class="position-relative">
                                        <div class="img-top position-relative product-img-holder">
                                            <img src="<?= validate_image($row['image_path']) ?>" alt="" class="product-img">
                                        </div>
                                        <div class="position-absolute bottom-1 right-1" style="bottom:.5em;right:.5em">
                                            <span class="badge badge-light bg-gradient-light border text-dark px-4 rounded-pill"><?= format_num($row['price'], 2) ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div style="line-height:1em">
                                            <div class="card-title w-100 mb-0"><?= $row['name'] ?></div>
                                            <div class="d-flex justify-content-between w-100 mb-3">
                                                <div class=""><small class="text-muted"><?= $row['brand'] ?></small></div>
                                                <div class=""><small class="text-muted">Stock: <?= format_num($row['available'], 0) ?></small></div>
                                            </div>
                                            <div class="card-description truncate text-muted"><?= html_entity_decode($row['description']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
