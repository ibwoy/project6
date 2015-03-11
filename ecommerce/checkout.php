<?php 
// This page inserts the order information into the table.
// This page would come after the billing process.
// This page assumes that the billing process worked (the money has been taken).

// Set the page title and include the HTML header.
$page_title = 'Order Confirmation';
include ('includes/header.html');

// Assume that the customer is logged in and that this page has access to the customer's ID:
$customer = $_SESSION['customer_id']; // Temporary.

// Assume that this page receives the order total and a out of stock flag for the second transaction.
$total = 1788.93; // Temporary.
$outstock = false;

require_once ('../mysqli_connect.php'); // Connect to the database.

// Turn autocommit off.
mysqli_autocommit($dbc, FALSE);


// Add the order to the orders table...
$q = "INSERT INTO orders (customer_id, total) VALUES ($customer, $total)";
$r = mysqli_query($dbc, $q);
if (mysqli_affected_rows($dbc) == 1) {

	// Need the order ID:
	$oid = mysqli_insert_id($dbc);
	
	// Insert the specific order contents into the database...
	
	// Prepare the query:
	$q = "INSERT INTO order_contents (order_id, print_id, quantity, price) VALUES (?, ?, ?, ?) ";
	$stmt = mysqli_prepare($dbc, $q);
	mysqli_stmt_bind_param($stmt, 'iiid', $oid, $pid, $qty, $price);
	
	
	
	
	// Execute each query, count the total affected:	
	$affected = 0;
	foreach ($_SESSION['cart'] as $pid => $item) {
		$qty = $item['quantity'];
		$price = $item['price'];
		
		//Prepare the query for quantity stock checkout
		
		$qstock = "SELECT quantity FROM prints, artists WHERE artists.artist_id = prints.artist_id AND print_id = $pid";
		$result = mysqli_query($dbc, $qstock);
		$row = mysqli_fetch_array($result);
		
		
		if($row[quantity] >= $qty) {
			mysqli_stmt_execute($stmt);
			$affected += mysqli_stmt_affected_rows($stmt);
			
			
			$newstock = $row[quantity] - $qty;
			$updateStock = "UPDATE prints SET quantity= $newstock WHERE print_id=$pid";
			$stmt2 = mysqli_prepare($dbc, $updateStock);
			mysqli_stmt_execute($stmt2);
			
		}
		else { 
			echo "{$row[quantity]} , {$qty}, {$pid}</p>";
			$outstock = true;}				
	}

	// Close this prepared statement:
	mysqli_stmt_close($stmt);

	// Report on the success....
	if ($affected == count($_SESSION['cart'])) { // Whohoo!
	
		// Commit the transaction:
		
		mysqli_commit($dbc);
		
		// Clear the cart.
		unset($_SESSION['cart']);
		
		// Message to the customer:
		echo '<p>Thank you for your order. You will be notified when the items ship.</p>';
		
		// Send emails and do whatever else.
	
	} else if($outstock){
		echo '<p>There are not enough prints in stock for your order. Try again with a smaller quantity.</p>';

	} 
	 else { // Rollback and report the problem.
	
		mysqli_rollback($dbc);
		
		echo '<p>Your order could not be processed due to a system error. You will be contacted in order to have the problem fixed. We apologize for the inconvenience.</p>';
		// Send the order information to the administrator.
		
	}

} else { // Rollback and report the problem.

	mysqli_rollback($dbc);

	echo '<p>Your order could not be processed due to a system error. You will be contacted in order to have the problem fixed. We apologize for the inconvenience.</p>';
	
	// Send the order information to the administrator.
	
}

mysqli_close($dbc);

include ('./includes/footer.html');
?>