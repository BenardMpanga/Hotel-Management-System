<!DOCTYPE html>
<html>
<head>
    <title>Admin Payments</title>
</head>
<style>
    body { margin: 0; background: #f2f2f2; }
    table { font-size: 22px; }
    td { text-align: center; }
    #td1 { background-color: rgba(09,41,98,0.9); color: white; border: 10px; margin-top: -10px; padding: 10px; }
    .basic_box { border: 1px solid #ccc; border-radius: 15px; margin: auto; width: 800px; padding: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.19); }
    ul { list-style-type: none; margin: 0; padding: 0; width: 22%; font-size: 24px; background-color: rgba(09,41,98,0.9); position: fixed; height: 100%; overflow: auto; }
    li { color: white; }
    li a { display: block; color: white; padding: 8px 16px; text-decoration: none; }
    li a.active { background-color: #e6b800; color: white; }
    li a:hover:not(.active) { background-color: #e6b800; color: white; text-decoration: underline; }
    .flash-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin: 12px 0; position: relative; transition: opacity 0.3s ease; }
    .flash-message .close-btn { position: absolute; top: 8px; right: 10px; background: transparent; border: none; color: #155724; font-size: 18px; cursor: pointer; }
</style>
<body>
    <table style="width: 100%;">
        <tr>
            <td id="td1" style="padding: 10px; font-size: 48px;">THE <p style="color: #e6b800; display: inline;">DELUXE</p> HOTEL</td>
        </tr>
    </table>
    <ul>
        <li><a href="admin_view.php">Rooms Info</a></li>
        <li><a href="add_room_admin.php">Add Room</a></li>
        <li><a href="remove_room_admin.php">Remove Rooms</a></li>
        <li><a href="admin_room_status.php">Booking Requests</a></li>
        <li><a href="confirmed_bookings.php">Confirmed Bookings</a></li>
        <li><a href="booking_history.php">Booking History</a></li>
        <li><a href="admin_payments.php" class="active">Payments</a></li>
        <li><a href="index.php">Logout</a></li>
    </ul>
    <div style="margin-left:25%;padding:1px 16px;">
        <?php
            $conn = new mysqli("localhost","root","", "iwp");
            if($conn->connect_error) { die("Connection failed: ".$conn->connect_error); }
            $book_id = isset($_GET['book_id']) ? $conn->real_escape_string($_GET['book_id']) : '';
            $name = isset($_GET['name']) ? $conn->real_escape_string($_GET['name']) : '';

            // Export CSV when requested
            if (isset($_GET['export']) && $_GET['export']=='1') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=payments.csv');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['booking_id','name','room_type','checkin','checkout','price','status']);

                // unpaid (confirmed_booking)
                $w = [];
                if ($book_id !== '') $w[] = "book_id='$book_id'";
                if ($name !== '') $w[] = "name LIKE '%$name%'";
                $where = count($w) ? ' WHERE '.implode(' AND ', $w) : '';
                $sql_unpaid = "SELECT book_id,name,room_type,checkin,checkout,price FROM confirmed_booking".$where;
                if ($resu = mysqli_query($conn, $sql_unpaid)) {
                    while ($r = mysqli_fetch_row($resu)) {
                        fputcsv($out, [$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], 'Unpaid']);
                    }
                }

                // paid (booked_hist)
                $w2 = [];
                if ($book_id !== '') $w2[] = "book_id='$book_id'";
                if ($name !== '') $w2[] = "name LIKE '%$name%'";
                $where2 = count($w2) ? ' WHERE '.implode(' AND ', $w2) : '';
                $sql_paid = "SELECT book_id,name,room_type,checkin,checkout,price FROM booked_hist".$where2;
                if ($resp = mysqli_query($conn, $sql_paid)) {
                    while ($rp = mysqli_fetch_row($resp)) {
                        fputcsv($out, [$rp[0], $rp[1], $rp[2], $rp[3], $rp[4], $rp[5], 'Paid']);
                    }
                }
                fclose($out);
                exit;
            }
        ?>

        <?php if (isset($_GET['status']) && $_GET['status']==='paid') {
            $paid_bid_msg = isset($_GET['book_id']) ? htmlspecialchars($_GET['book_id']) : '';
            echo '<div id="flashMessage" class="flash-message">Booking '.($paid_bid_msg?:'').' marked as paid successfully.<button type="button" class="close-btn" onclick="document.getElementById(\'flashMessage\').style.display=\'none\';">&times;</button></div>';
        } ?>

        <div class="basic_box" style="max-width:900px;">
            <form method="get" action="admin_payments.php" style="margin-bottom:18px; display:flex; gap:8px; align-items:center;">
                <div>
                    <label>Booking ID:</label><br>
                    <input type="text" name="book_id" value="<?php echo htmlspecialchars($book_id); ?>">
                </div>
                <div>
                    <label>Name:</label><br>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                </div>
                <div style="margin-top:20px;">
                    <button type="submit">Filter</button>
                    <a href="admin_payments.php?export=1&book_id=<?php echo urlencode($book_id); ?>&name=<?php echo urlencode($name); ?>" style="margin-left:8px;">Export CSV</a>
                </div>
            </form>

            <p style="font-size: 24px; text-align: left;"><b>Unpaid (Confirmed Bookings)</b></p>
            <table style="width:100%; border-collapse: collapse;">
                <tr>
                    <th>Booking ID</th>
                    <th>Name</th>
                    <th>Room Type</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
                <?php
                    $w = [];
                    if ($book_id !== '') $w[] = "book_id='$book_id'";
                    if ($name !== '') $w[] = "name LIKE '%$name%'";
                    $where = count($w) ? ' WHERE '.implode(' AND ', $w) : '';
                    $sql = "SELECT book_id,name,room_type,checkin,checkout,price FROM confirmed_booking".$where;
                    if ($res = mysqli_query($conn, $sql)) {
                        while ($r = mysqli_fetch_row($res)) {
                            echo '<tr>';
                            echo '<td>'.htmlspecialchars($r[0]).'</td>';
                            echo '<td>'.htmlspecialchars($r[1]).'</td>';
                            echo '<td>'.htmlspecialchars($r[2]).'</td>';
                            echo '<td>'.htmlspecialchars($r[3]).'</td>';
                            echo '<td>'.htmlspecialchars($r[4]).'</td>';
                            echo '<td>'.htmlspecialchars($r[5]).'</td>';
                            echo '<td>';
                            echo '<form method="post" action="admin_mark_paid.php" style="margin:0;">';
                            echo '<input type="hidden" name="book_id" value="'.htmlspecialchars($r[0]).'">';
                            echo '<button type="submit" onclick="return confirm(\'Mark booking '.htmlspecialchars($r[0]).' as paid?\')">Mark paid</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        mysqli_free_result($res);
                    }
                ?>
            </table>
        </div>

        <br>
        <div class="basic_box" style="max-width:900px;">
            <p style="font-size: 24px; text-align: left;"><b>Paid (Booking History)</b></p>
            <table style="width:100%; border-collapse: collapse;">
                <tr>
                    <th>Booking ID</th>
                    <th>Name</th>
                    <th>Room Type</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Price</th>
                </tr>
                <?php
                    $w2 = [];
                    if ($book_id !== '') $w2[] = "book_id='$book_id'";
                    if ($name !== '') $w2[] = "name LIKE '%$name%'";
                    $where2 = count($w2) ? ' WHERE '.implode(' AND ', $w2) : '';
                    $sql2 = "SELECT book_id,name,room_type,checkin,checkout,price FROM booked_hist".$where2;
                    if ($res2 = mysqli_query($conn, $sql2)) {
                        while ($r2 = mysqli_fetch_row($res2)) {
                            echo '<tr>';
                            echo '<td>'.htmlspecialchars($r2[0]).'</td>';
                            echo '<td>'.htmlspecialchars($r2[1]).'</td>';
                            echo '<td>'.htmlspecialchars($r2[2]).'</td>';
                            echo '<td>'.htmlspecialchars($r2[3]).'</td>';
                            echo '<td>'.htmlspecialchars($r2[4]).'</td>';
                            echo '<td>'.htmlspecialchars($r2[5]).'</td>';
                            echo '</tr>';
                        }
                        mysqli_free_result($res2);
                    }
                    $conn->close();
                ?>
            </table>
        </div>
    </div>
    <script>
        window.addEventListener('load', function() {
            var flash = document.getElementById('flashMessage');
            if (flash) {
                setTimeout(function() {
                    flash.style.opacity = '0';
                    setTimeout(function() { flash.style.display = 'none'; }, 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>
