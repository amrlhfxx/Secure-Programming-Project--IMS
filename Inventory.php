<?php
class Inventory {
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $database = 'ims_db';
    private $userTable = 'ims_user';
    private $customerTable = 'ims_customer';
    private $categoryTable = 'ims_category';
    private $brandTable = 'ims_brand';
    private $productTable = 'ims_product';
    private $supplierTable = 'ims_supplier';
    private $purchaseTable = 'ims_purchase';
    private $orderTable = 'ims_order';
    private $dbConnect = false;
    public function __construct() {
        if ( !$this->dbConnect ) {
            $conn = new mysqli( $this->host, $this->user, $this->password, $this->database );
            if ( $conn->connect_error ) {
                die( "Error failed to connect to MySQL: " . $conn->connect_error );
            } else {
                $this->dbConnect = $conn;
            }
        }
    }
    private function getData( $sqlQuery ) {
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        if ( !$result ) {
            die( 'Error in query: ' . mysqli_error() );
        }
        $data = array();
        while ( $row = mysqli_fetch_array( $result, MYSQLI_ASSOC ) ) {
            $data[] = $row;
        }
        return $data;
    }
    private function getNumRows( $sqlQuery ) {
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        if ( !$result ) {
            die( 'Error in query: ' . mysqli_error() );
        }
        $numRows = mysqli_num_rows( $result );
        return $numRows;
    }

    #Login
    public function login( $email, $password ) {
        $email = mysqli_real_escape_string( $this->dbConnect, $email );
        $sqlQuery = "SELECT userid, email, password, salt, name, type, status
                 FROM " . $this->userTable . " 
                 WHERE email='" . $email . "'";
        $userData = $this->getData( $sqlQuery );
        if ( !empty( $userData ) ) {
            $hashedPassword = $userData[ 0 ][ 'password' ];
            $salt = $userData[ 0 ][ 'salt' ];
            if ( password_verify( $password . $salt, $hashedPassword ) ) {
                return $userData;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function checkLogin() {
        if ( empty( $_SESSION[ 'userid' ] ) ) {
            header( "Location:login.php" );
        }
    }

    # Register
    public function register( $name, $email, $password ) {
        $name = mysqli_real_escape_string( $this->dbConnect, $name );
        $email = mysqli_real_escape_string( $this->dbConnect, $email );
        $salt = uniqid( mt_rand(), true );
        $hashedPassword = password_hash( $password . $salt, PASSWORD_DEFAULT );
        $verificationCode = generateVerificationCode();
        $sql = "INSERT INTO ims_user (email, password, salt, name, type, status, verification_code) 
            VALUES ('$email', '$hashedPassword', '$salt', '$name', 'member', 'Inactive', '$verificationCode')";
        if ( mysqli_query( $this->dbConnect, $sql ) ) {
            $subject = 'Verify Your Email Address';
            $message = 'Your verification code is: ' . $verificationCode;
            if ( sendEmail( $email, $subject, $message ) ) {
                return true;
            } else {
                return "Error sending verification email.";
            }
        } else {
            return "Error: " . $sql . "<br>" . mysqli_error( $this->dbConnect );
        }
    }

    #Verify
    public function verifyEmail( $email, $verificationCode ) {
        $email = mysqli_real_escape_string( $this->dbConnect, $email );
        $verificationCode = mysqli_real_escape_string( $this->dbConnect, $verificationCode );
        $sql = "SELECT email FROM ims_user WHERE email='$email' AND verification_code='$verificationCode'";
        $result = mysqli_query( $this->dbConnect, $sql );
        if ( mysqli_num_rows( $result ) == 1 ) {
            $sqlUpdate = "UPDATE ims_user SET status='Active' WHERE email='$email'";
            mysqli_query( $this->dbConnect, $sqlUpdate );
            return true;
        } else {
            return false;
        }
    }


    //Customer Function 
    public function getCustomer() {
        $sqlQuery = "
			SELECT * FROM " . $this->customerTable . " 
			WHERE id = '" . $_POST[ "userid" ] . "'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
        echo json_encode( $row );
    }

    public function getCustomerList() {
        $sqlQuery = "SELECT * FROM " . $this->customerTable . " ";
        if ( !empty( $_POST[ "search" ][ "value" ] ) ) {
            $sqlQuery .= '(id LIKE "%' . $_POST[ "search" ][ "value" ] . '%" ';
            $sqlQuery .= '(name LIKE "%' . $_POST[ "search" ][ "value" ] . '%" ';
            $sqlQuery .= 'OR address LIKE "%' . $_POST[ "search" ][ "value" ] . '%" ';
            $sqlQuery .= 'OR mobile LIKE "%' . $_POST[ "search" ][ "value" ] . '%") ';
            $sqlQuery .= 'OR balance LIKE "%' . $_POST[ "search" ][ "value" ] . '%") ';
        }
        if ( !empty( $_POST[ "order" ] ) ) {
            $sqlQuery .= 'ORDER BY ' . $_POST[ 'order' ][ '0' ][ 'column' ] . ' ' . $_POST[ 'order' ][ '0' ][ 'dir' ] . ' ';
        } else {
            $sqlQuery .= 'ORDER BY id DESC ';
        }
        if ( $_POST[ "length" ] != -1 ) {
            $sqlQuery .= 'LIMIT ' . $_POST[ 'start' ] . ', ' . $_POST[ 'length' ];
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $customerData = array();
        while ( $customer = mysqli_fetch_assoc( $result ) ) {
            $customerRows = array();
            $customerRows[] = $customer[ 'id' ];
            $customerRows[] = $customer[ 'name' ];
            $customerRows[] = $customer[ 'address' ];
            $customerRows[] = $customer[ 'mobile' ];
            $customerRows[] = number_format( $customer[ 'balance' ], 2 );
            $customerRows[] = '<button type="button" name="update" id="' . $customer[ "id" ] . '" class="btn btn-primary btn-sm rounded-0 update" title="update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . $customer[ "id" ] . '" class="btn btn-danger btn-sm rounded-0 delete" ><i class="fa fa-trash"></button>';
            $customerRows[] = '';
            $customerData[] = $customerRows;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $customerData
        );
        echo json_encode( $output );
    }

    public function saveCustomer() {
        $name = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'cname' ] );
        $address = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'address' ] );
        $mobile = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'mobile' ] );
        $balance = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'balance' ] );
        $sqlInsert = "INSERT INTO " . $this->customerTable . "(name, address, mobile, balance) VALUES (?, ?, ?, ?)";
        $stmt = $this->dbConnect->prepare( $sqlInsert );
        $stmt->bind_param( "ssss", $name, $address, $mobile, $balance );
        $stmt->execute();
        echo 'New Customer Added';
    }

    public function updateCustomer() {
        $name = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'cname' ] );
        $address = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'address' ] );
        $mobile = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'mobile' ] );
        $balance = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'balance' ] );
        $userid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'userid' ] );
        $sqlUpdate = "UPDATE " . $this->customerTable . " SET name = ?, address = ?, mobile = ?, balance = ? WHERE id = ?";
        $stmt = $this->dbConnect->prepare( $sqlUpdate );
        $stmt->bind_param( "ssssi", $name, $address, $mobile, $balance, $userid );
        $stmt->execute();
        echo 'Customer Edited';
    }

    public function deleteCustomer() {
        $sqlQuery = "
			DELETE FROM " . $this->customerTable . " 
			WHERE id = '" . $_POST[ 'userid' ] . "'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }


    // Category Function
    public function getCategoryList() {
        $sqlQuery = "SELECT * FROM " . $this->categoryTable . " ";
        if ( !empty( $_POST[ "search" ][ "value" ] ) ) {
            $searchValue = mysqli_real_escape_string( $this->dbConnect, $_POST[ "search" ][ "value" ] );
            $sqlQuery .= "WHERE (name LIKE '%$searchValue%' OR status LIKE '%$searchValue%') ";
        }
        if ( !empty( $_POST[ "order" ] ) ) {
            $columnIndex = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'column' ] );
            $columnDir = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'dir' ] );
            $sqlQuery .= "ORDER BY $columnIndex $columnDir ";
        } else {
            $sqlQuery .= "ORDER BY categoryid DESC ";
        }
        if ( $_POST[ "length" ] != -1 ) {
            $start = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'start' ] );
            $length = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'length' ] );
            $sqlQuery .= "LIMIT $start, $length";
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $categoryData = array();
        while ( $category = mysqli_fetch_assoc( $result ) ) {
            $status = ( $category[ 'status' ] == 'active' ) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
            $categoryRows = array(
                $category[ 'categoryid' ],
                htmlspecialchars( $category[ 'name' ] ), 
                $status,
                '<button type="button" name="update" id="' . $category[ "categoryid" ] . '" class="btn btn-primary btn-sm rounded-0 update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . $category[ "categoryid" ] . '" class="btn btn-danger btn-sm rounded-0 delete"  title="Delete"><i class="fa fa-trash"></i></button>'
            );

            $categoryData[] = $categoryRows;
        }

        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $categoryData
        );
        echo json_encode( $output );
    }

    public function saveCategory() {
        $categoryName = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'category' ] );
        $sqlInsert = "INSERT INTO " . $this->categoryTable . "(name) VALUES ('$categoryName')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New Category Added';
    }

    public function getCategory() {
        $categoryId = mysqli_real_escape_string( $this->dbConnect, $_POST[ "categoryId" ] );
        $sqlQuery = "SELECT * FROM " . $this->categoryTable . " WHERE categoryid = '$categoryId'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
        echo json_encode( $row );
    }

    public function updateCategory() {
        $categoryName = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'category' ] );
        $categoryId = mysqli_real_escape_string( $this->dbConnect, $_POST[ "categoryId" ] );
        $sqlInsert = "UPDATE " . $this->categoryTable . " SET name = '$categoryName' WHERE categoryid = '$categoryId'";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'Category Update';
    }

    public function deleteCategory() {
        $categoryId = mysqli_real_escape_string( $this->dbConnect, $_POST[ "categoryId" ] );
        $sqlQuery = "DELETE FROM " . $this->categoryTable . " WHERE categoryid = '$categoryId'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }

    // Brand Function
    public function getBrandList() {
        $sqlQuery = "SELECT * FROM " . $this->brandTable . " as b 
        INNER JOIN " . $this->categoryTable . " as c ON c.categoryid = b.categoryid ";
        if ( !empty( $_POST[ "search" ][ "value" ] ) ) {
            $search = mysqli_real_escape_string( $this->dbConnect, $_POST[ "search" ][ "value" ] );
            $sqlQuery .= 'WHERE b.bname LIKE "%' . $search . '%" ';
            $sqlQuery .= 'OR c.name LIKE "%' . $search . '%" ';
            $sqlQuery .= 'OR b.status LIKE "%' . $search . '%" ';
        }
        if ( !empty( $_POST[ "order" ] ) ) {
            $column = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'column' ] );
            $dir = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'dir' ] );
            $sqlQuery .= 'ORDER BY ' . $column . ' ' . $dir . ' ';
        } else {
            $sqlQuery .= 'ORDER BY b.id DESC ';
        }
        if ( $_POST[ "length" ] != -1 ) {
            $start = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'start' ] );
            $length = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'length' ] );
            $sqlQuery .= 'LIMIT ' . $start . ', ' . $length;
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $brandData = array();
        while ( $brand = mysqli_fetch_assoc( $result ) ) {
            $status = '';
            if ( $brand[ 'status' ] == 'active' ) {
                $status = '<span class="label label-success">Active</span>';
            } else {
                $status = '<span class="label label-danger">Inactive</span>';
            }
            $brandRows = array();
            $brandRows[] = htmlspecialchars( $brand[ 'id' ] );
            $brandRows[] = htmlspecialchars( $brand[ 'bname' ] );
            $brandRows[] = htmlspecialchars( $brand[ 'name' ] );
            $brandRows[] = $status;
            $brandRows[] = '<button type="button" name="update" id="' . htmlspecialchars( $brand[ "id" ] ) . '" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . htmlspecialchars( $brand[ "id" ] ) . '" class="btn btn-danger btn-sm rounded-0  delete" data-status="' . htmlspecialchars( $brand[ "status" ] ) . '" title="Delete"><i class="fa fa-trash"></i></button>';
            $brandData[] = $brandRows;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $brandData
        );
        echo json_encode( $output );
    }

    public function categoryDropdownList() {
        $sqlQuery = "SELECT * FROM " . $this->categoryTable . " 
        WHERE status = 'active' 
        ORDER BY name ASC";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $categoryHTML = '';
        while ( $category = mysqli_fetch_assoc( $result ) ) {
            $categoryHTML .= '<option value="' . htmlspecialchars( $category[ "categoryid" ] ) . '">' . htmlspecialchars( $category[ "name" ] ) . '</option>';
        }
        return $categoryHTML;
    }

    public function saveBrand() {
        $categoryid = mysqli_real_escape_string( $this->dbConnect, $_POST[ "categoryid" ] );
        $bname = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'bname' ] );
        $sqlInsert = "
        INSERT INTO " . $this->brandTable . "(categoryid, bname) 
        VALUES ('" . $categoryid . "', '" . $bname . "')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New Brand Added';
    }

    public function updateBrand() {
        $id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'id' ] );
        $bname = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'bname' ] );
        $categoryid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'categoryid' ] );
        if ( $id ) {
            $sqlUpdate = "UPDATE " . $this->brandTable . " SET bname = '" . $bname . "', categoryid='" . $categoryid . "' WHERE id = '" . $id . "'";
            mysqli_query( $this->dbConnect, $sqlUpdate );
            echo 'Brand Update';
        }
    }

    public function deleteBrand() {
        $sqlQuery = "
			DELETE FROM " . $this->brandTable . " 
			WHERE id = '" . $_POST[ "id" ] . "'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }


    // Product Function
    public function getProductList() {
        $sqlQuery = "SELECT * FROM " . $this->productTable . " as p
            INNER JOIN " . $this->brandTable . " as b ON b.id = p.brandid
            INNER JOIN " . $this->categoryTable . " as c ON c.categoryid = p.categoryid 
            INNER JOIN " . $this->supplierTable . " as s ON s.supplier_id = p.supplier ";
        if ( isset( $_POST[ "search" ][ "value" ] ) ) {
            $searchValue = mysqli_real_escape_string( $this->dbConnect, $_POST[ "search" ][ "value" ] );
            $sqlQuery .= 'WHERE b.bname LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= 'OR c.name LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= 'OR p.pname LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= 'OR p.quantity LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= 'OR s.supplier_name LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= 'OR p.pid LIKE "%' . $searchValue . '%" ';
        }
        if ( isset( $_POST[ 'order' ] ) ) {
            $column = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'column' ] );
            $dir = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'dir' ] );
            $sqlQuery .= 'ORDER BY ' . $column . ' ' . $dir . ' ';
        } else {
            $sqlQuery .= 'ORDER BY p.pid DESC ';
        }
        if ( $_POST[ 'length' ] != -1 ) {
            $start = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'start' ] );
            $length = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'length' ] );
            $sqlQuery .= 'LIMIT ' . $start . ', ' . $length;
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $productData = array();
        while ( $product = mysqli_fetch_assoc( $result ) ) {
            $status = ( $product[ 'status' ] == 'active' ) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
            $productRow = array();
            $productRow[] = $product[ 'pid' ];
            $productRow[] = htmlspecialchars( $product[ 'name' ] );
            $productRow[] = htmlspecialchars( $product[ 'bname' ] );
            $productRow[] = htmlspecialchars( $product[ 'pname' ] );
            $productRow[] = htmlspecialchars( $product[ "model" ] );
            $productRow[] = htmlspecialchars( $product[ "quantity" ] );
            $productRow[] = htmlspecialchars( $product[ 'supplier_name' ] );
            $productRow[] = $status;
            $productRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="view" id="' . $product[ "pid" ] . '" class="btn btn-light bg-gradient border text-dark btn-sm rounded-0  view" title="View"><i class="fa fa-eye"></i></button><button type="button" name="update" id="' . $product[ "pid" ] . '" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . $product[ "pid" ] . '" class="btn btn-danger btn-sm rounded-0  delete" data-status="' . $product[ "status" ] . '" title="Delete"><i class="fa fa-trash"></i></button></div>';
            $productData[] = $productRow;
        }
        $outputData = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $productData
        );
        echo json_encode( $outputData );
    }

    public function getCategoryBrand( $categoryid ) {
        $sqlQuery = "SELECT * FROM " . $this->brandTable . " 
            WHERE status = 'active' AND categoryid = '" . mysqli_real_escape_string( $this->dbConnect, $categoryid ) . "' ORDER BY bname ASC";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $dropdownHTML = '';
        while ( $brand = mysqli_fetch_assoc( $result ) ) {
            $dropdownHTML .= '<option value="' . htmlspecialchars( $brand[ "id" ] ) . '">' . htmlspecialchars( $brand[ "bname" ] ) . '</option>';
        }
        return $dropdownHTML;
    }

    public function supplierDropdownList() {
        $sqlQuery = "SELECT * FROM " . $this->supplierTable . " 
            WHERE status = 'active' ORDER BY supplier_name ASC";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $dropdownHTML = '';
        while ( $supplier = mysqli_fetch_assoc( $result ) ) {
            $dropdownHTML .= '<option value="' . htmlspecialchars( $supplier[ "supplier_id" ] ) . '">' . htmlspecialchars( $supplier[ "supplier_name" ] ) . '</option>';
        }
        return $dropdownHTML;
    }

    public function addProduct() {
        $categoryid = mysqli_real_escape_string( $this->dbConnect, $_POST[ "categoryid" ] );
        $brandid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'brandid' ] );
        $pname = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'pname' ] );
        $pmodel = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'pmodel' ] );
        $description = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'description' ] );
        $quantity = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'quantity' ] );
        $unit = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'unit' ] );
        $base_price = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'base_price' ] );
        $tax = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'tax' ] );
        $supplierid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplierid' ] );
        $sqlInsert = "INSERT INTO " . $this->productTable . "(categoryid, brandid, pname, model, description, quantity, unit, base_price, tax, minimum_order, supplier) 
            VALUES ('$categoryid', '$brandid', '$pname', '$pmodel', '$description', '$quantity', '$unit', '$base_price', '$tax', 1, '$supplierid')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New Product Added';
    }

    public function getProductDetails() {
        $sqlQuery = "SELECT * FROM " . $this->productTable . " 
            WHERE pid = '" . mysqli_real_escape_string( $this->dbConnect, $_POST[ "pid" ] ) . "'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $output = array();
        while ( $product = mysqli_fetch_assoc( $result ) ) {
            $output[ 'pid' ] = htmlspecialchars( $product[ 'pid' ] );
            $output[ 'categoryid' ] = htmlspecialchars( $product[ 'categoryid' ] );
            $output[ 'brandid' ] = htmlspecialchars( $product[ 'brandid' ] );
            $output[ "brand_select_box" ] = $this->getCategoryBrand( $product[ 'categoryid' ] );
            $output[ 'pname' ] = htmlspecialchars( $product[ 'pname' ] );
            $output[ 'model' ] = htmlspecialchars( $product[ 'model' ] );
            $output[ 'description' ] = htmlspecialchars( $product[ 'description' ] );
            $output[ 'quantity' ] = htmlspecialchars( $product[ 'quantity' ] );
            $output[ 'unit' ] = htmlspecialchars( $product[ 'unit' ] );
            $output[ 'base_price' ] = htmlspecialchars( $product[ 'base_price' ] );
            $output[ 'tax' ] = htmlspecialchars( $product[ 'tax' ] );
            $output[ 'supplier' ] = htmlspecialchars( $product[ 'supplier' ] );
        }
        echo json_encode( $output );
    }

    public function updateProduct() {
        if ( $_POST[ 'pid' ] ) {
            $categoryid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'categoryid' ] );
            $brandid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'brandid' ] );
            $pname = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'pname' ] );
            $pmodel = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'pmodel' ] );
            $description = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'description' ] );
            $quantity = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'quantity' ] );
            $unit = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'unit' ] );
            $base_price = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'base_price' ] );
            $tax = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'tax' ] );
            $supplierid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplierid' ] );
            $sqlUpdate = "UPDATE " . $this->productTable . " 
                SET categoryid = '$categoryid', brandid='$brandid', pname='$pname', model='$pmodel', description='$description', quantity='$quantity', unit='$unit', base_price='$base_price', tax='$tax', supplier='$supplierid' WHERE pid = '" . $_POST[ "pid" ] . "'";
            mysqli_query( $this->dbConnect, $sqlUpdate );
            echo 'Product Update';
        }
    }

    public function deleteProduct() {
        $sqlQuery = "
            DELETE FROM " . $this->productTable . " 
            WHERE pid = '" . mysqli_real_escape_string( $this->dbConnect, $_POST[ "pid" ] ) . "'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }

    public function viewProductDetails() {
        $sqlQuery = "SELECT * FROM " . $this->productTable . " as p
            INNER JOIN " . $this->brandTable . " as b ON b.id = p.brandid
            INNER JOIN " . $this->categoryTable . " as c ON c.categoryid = p.categoryid 
            INNER JOIN " . $this->supplierTable . " as s ON s.supplier_id = p.supplier 
            WHERE p.pid = '" . mysqli_real_escape_string( $this->dbConnect, $_POST[ "pid" ] ) . "'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $productDetails = '<div class="table-responsive">
                <table class="table table-boredered">';
        while ( $product = mysqli_fetch_assoc( $result ) ) {
            $status = ( $product[ 'status' ] == 'active' ) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
            $productDetails .= '
            <tr>
                <td>Product Name</td>
                <td>' . htmlspecialchars( $product[ "pname" ] ) . '</td>
            </tr>
            <tr>
                <td>Product Model</td>
                <td>' . htmlspecialchars( $product[ "model" ] ) . '</td>
            </tr>
            <tr>
                <td>Product Description</td>
                <td>' . htmlspecialchars( $product[ "description" ] ) . '</td>
            </tr>
            <tr>
                <td>Category</td>
                <td>' . htmlspecialchars( $product[ "name" ] ) . '</td>
            </tr>
            <tr>
                <td>Brand</td>
                <td>' . htmlspecialchars( $product[ "bname" ] ) . '</td>
            </tr>            
            <tr>
                <td>Available Quantity</td>
                <td>' . htmlspecialchars( $product[ "quantity" ] ) . ' ' . htmlspecialchars( $product[ "unit" ] ) . '</td>
            </tr>
            <tr>
                <td>Base Price</td>
                <td>' . htmlspecialchars( $product[ "base_price" ] ) . '</td>
            </tr>
            <tr>
                <td>Tax (%)</td>
                <td>' . htmlspecialchars( $product[ "tax" ] ) . '</td>
            </tr>
            <tr>
                <td>Enter By</td>
                <td>' . htmlspecialchars( $product[ "supplier_name" ] ) . '</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>' . $status . '</td>
            </tr>
            ';
        }
        $productDetails .= '
            </table>
        </div>
        ';
        echo $productDetails;
    }


    // Supplier Function
    public function getSupplierList() {
        $sqlQuery = "SELECT * FROM " . $this->supplierTable . " ";
        if ( !empty( $_POST[ "search" ][ "value" ] ) ) {
            $searchValue = mysqli_real_escape_string( $this->dbConnect, $_POST[ "search" ][ "value" ] );
            $sqlQuery .= 'WHERE (supplier_name LIKE "%' . $searchValue . '%" ';
            $sqlQuery .= '(address LIKE "%' . $searchValue . '%" ';
        }
        if ( !empty( $_POST[ "order" ] ) ) {
            $columnIndex = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'column' ] );
            $columnDir = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order' ][ '0' ][ 'dir' ] );
            $sqlQuery .= 'ORDER BY ' . $columnIndex . ' ' . $columnDir . ' ';
        } else {
            $sqlQuery .= 'ORDER BY supplier_id DESC ';
        }
        if ( $_POST[ "length" ] != -1 ) {
            $start = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'start' ] );
            $length = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'length' ] );
            $sqlQuery .= 'LIMIT ' . $start . ', ' . $length;
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $supplierData = array();
        while ( $supplier = mysqli_fetch_assoc( $result ) ) {
            $supplier_id = htmlspecialchars( $supplier[ 'supplier_id' ] );
            $supplier_name = htmlspecialchars( $supplier[ 'supplier_name' ] );
            $mobile = htmlspecialchars( $supplier[ 'mobile' ] );
            $address = htmlspecialchars( $supplier[ 'address' ] );
            $status = '';
            if ( $supplier[ 'status' ] == 'active' ) {
                $status = '<span class="label label-success">Active</span>';
            } else {
                $status = '<span class="label label-danger">Inactive</span>';
            }
            $supplierRows = array();
            $supplierRows[] = $supplier_id;
            $supplierRows[] = $supplier_name;
            $supplierRows[] = $mobile;
            $supplierRows[] = $address;
            $supplierRows[] = $status;
            $supplierRows[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="' . $supplier_id . '" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . $supplier_id . '" class="btn btn-danger btn-sm rounded-0  delete"  title="Delete"><i class="fa fa-trash"></i></button></div>';
            $supplierData[] = $supplierRows;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $supplierData
        );
        echo json_encode( $output );
    }

    public function addSupplier() {
        $supplier_name = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplier_name' ] );
        $mobile = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'mobile' ] );
        $address = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'address' ] );
        $sqlInsert = "
        INSERT INTO " . $this->supplierTable . "(supplier_name, mobile, address) 
        VALUES ('" . $supplier_name . "', '" . $mobile . "', '" . $address . "')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New Supplier Added';
    }

    public function getSupplier() {
        $supplier_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ "supplier_id" ] );
        $sqlQuery = "
        SELECT * FROM " . $this->supplierTable . " 
        WHERE supplier_id = '" . $supplier_id . "'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
        echo json_encode( $row );
    }

    public function updateSupplier() {
        if ( $_POST[ 'supplier_id' ] ) {
            $supplier_name = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplier_name' ] );
            $mobile = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'mobile' ] );
            $address = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'address' ] );
            $sqlUpdate = "
            UPDATE " . $this->supplierTable . " 
            SET supplier_name = '" . $supplier_name . "', mobile= '" . $mobile . "' , address= '" . $address . "' WHERE supplier_id = '" . $supplier_id . "'";
            mysqli_query( $this->dbConnect, $sqlUpdate );
            echo 'Supplier Edited';
        }
    }

    public function deleteSupplier() {
        $supplier_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplier_id' ] );
        $sqlQuery = "
        DELETE FROM " . $this->supplierTable . " 
        WHERE supplier_id = '" . $supplier_id . "'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }


    // Purchase Function
    public function listPurchase() {
        $sqlQuery = "SELECT ph.*, p.pname, s.supplier_name FROM " . $this->purchaseTable . " as ph
            INNER JOIN " . $this->productTable . " as p ON p.pid = ph.product_id 
            INNER JOIN " . $this->supplierTable . " as s ON s.supplier_id = ph.supplier_id ";
        if ( isset( $_POST[ 'order' ] ) ) {
            $sqlQuery .= 'ORDER BY ' . $_POST[ 'order' ][ '0' ][ 'column' ] . ' ' . $_POST[ 'order' ][ '0' ][ 'dir' ] . ' ';
        } else {
            $sqlQuery .= 'ORDER BY ph.purchase_id DESC ';
        }
        if ( $_POST[ 'length' ] != -1 ) {
            $sqlQuery .= 'LIMIT ' . $_POST[ 'start' ] . ', ' . $_POST[ 'length' ];
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $purchaseData = array();
        while ( $purchase = mysqli_fetch_assoc( $result ) ) {
            $productRow = array();
            $productRow[] = htmlspecialchars( $purchase[ 'purchase_id' ] );
            $productRow[] = htmlspecialchars( $purchase[ 'pname' ] );
            $productRow[] = htmlspecialchars( $purchase[ 'quantity' ] );
            $productRow[] = htmlspecialchars( $purchase[ 'supplier_name' ] );
            $productRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="' . htmlspecialchars( $purchase[ "purchase_id" ] ) . '" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . htmlspecialchars( $purchase[ "purchase_id" ] ) . '" class="btn btn-danger btn-sm rounded-0  delete" title="Delete"><i class="fa fa-trash"></i></button></div>';
            $purchaseData[] = $productRow;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $purchaseData
        );
        echo json_encode( $output );
    }

    public function productDropdownList() {
        $sqlQuery = "SELECT * FROM " . $this->productTable . " ORDER BY pname ASC";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $dropdownHTML = '';
        while ( $product = mysqli_fetch_assoc( $result ) ) {
            $dropdownHTML .= '<option value="' . htmlspecialchars( $product[ "pid" ] ) . '">' . htmlspecialchars( $product[ "pname" ] ) . '</option>';
        }
        return $dropdownHTML;
    }

    public function addPurchase() {
        $product = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'product' ] );
        $quantity = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'quantity' ] );
        $supplierid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplierid' ] );
        $sqlInsert = "
            INSERT INTO " . $this->purchaseTable . "(product_id, quantity, supplier_id) 
            VALUES ('$product', '$quantity', '$supplierid')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New Purchase Added';
    }

    public function getPurchaseDetails() {
        $purchase_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ "purchase_id" ] );
        $sqlQuery = "
            SELECT * FROM " . $this->purchaseTable . " 
            WHERE purchase_id = '$purchase_id'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
        echo json_encode( $row );
    }

    public function updatePurchase() {
        $product = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'product' ] );
        $quantity = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'quantity' ] );
        $supplierid = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'supplierid' ] );
        $purchase_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'purchase_id' ] );
        $sqlUpdate = "
            UPDATE " . $this->purchaseTable . " 
            SET product_id = '$product', quantity= '$quantity' , supplier_id= '$supplierid' WHERE purchase_id = '$purchase_id'";
        mysqli_query( $this->dbConnect, $sqlUpdate );
        echo 'Purchase Edited';
    }

    public function deletePurchase() {
        $purchase_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'purchase_id' ] );
        $sqlQuery = "
            DELETE FROM " . $this->purchaseTable . " 
            WHERE purchase_id = '$purchase_id'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }


    // Order Function
    public function listOrders() {
        $sqlQuery = "SELECT * FROM " . $this->orderTable . " as o
            INNER JOIN " . $this->customerTable . " as c ON c.id = o.customer_id
            INNER JOIN " . $this->productTable . " as p ON p.pid = o.product_id ";
        if ( isset( $_POST[ 'order' ] ) ) {
            $sqlQuery .= 'ORDER BY ' . $_POST[ 'order' ][ '0' ][ 'column' ] . ' ' . $_POST[ 'order' ][ '0' ][ 'dir' ] . ' ';
        } else {
            $sqlQuery .= 'ORDER BY o.order_id DESC ';
        }
        if ( $_POST[ 'length' ] != -1 ) {
            $sqlQuery .= 'LIMIT ' . $_POST[ 'start' ] . ', ' . $_POST[ 'length' ];
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $orderData = array();
        while ( $order = mysqli_fetch_assoc( $result ) ) {
            $orderRow = array();
            $orderRow[] = $order[ 'order_id' ];
            $orderRow[] = $order[ 'pname' ];
            $orderRow[] = $order[ 'total_shipped' ];
            $orderRow[] = $order[ 'name' ];
            $orderRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="' . $order[ "order_id" ] . '" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="' . $order[ "order_id" ] . '" class="btn btn-danger btn-sm rounded-0  delete" title="Delete"><i class="fa fa-trash"></i></button></button';
            $orderData[] = $orderRow;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $orderData
        );
        echo json_encode( $output );
    }

    public function addOrder() {
        $product = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'product' ] );
        $shipped = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'shipped' ] );
        $customer = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'customer' ] );

        $sqlInsert = "
            INSERT INTO " . $this->orderTable . "(product_id, total_shipped, customer_id) 
            VALUES ('$product', '$shipped', '$customer')";
        mysqli_query( $this->dbConnect, $sqlInsert );
        echo 'New order added';
    }

    public function getOrderDetails() {
        $order_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ "order_id" ] );
        $sqlQuery = "
            SELECT * FROM " . $this->orderTable . " 
            WHERE order_id = '$order_id'";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
        foreach ( $row as & $value ) {
            $value = htmlspecialchars( $value, ENT_QUOTES );
        }
        echo json_encode( $row );
    }

    public function updateOrder() {
        $product = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'product' ] );
        $shipped = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'shipped' ] );
        $customer = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'customer' ] );
        $order_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order_id' ] );

        $sqlUpdate = "
            UPDATE " . $this->orderTable . " 
            SET product_id = '$product', total_shipped='$shipped', customer_id='$customer' WHERE order_id = '$order_id'";
        mysqli_query( $this->dbConnect, $sqlUpdate );
        echo 'Order Edited';
    }

    public function deleteOrder() {
        $order_id = mysqli_real_escape_string( $this->dbConnect, $_POST[ 'order_id' ] );
        $sqlQuery = "
            DELETE FROM " . $this->orderTable . " 
            WHERE order_id = '$order_id'";
        mysqli_query( $this->dbConnect, $sqlQuery );
    }

    public function customerDropdownList() {
        $sqlQuery = "SELECT * FROM " . $this->customerTable . " ORDER BY name ASC";
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $dropdownHTML = '';
        while ( $customer = mysqli_fetch_assoc( $result ) ) {
            $dropdownHTML .= '<option value="' . $customer[ "id" ] . '">' . htmlspecialchars( $customer[ "name" ], ENT_QUOTES ) . '</option>';
        }
        return $dropdownHTML;
    }

    public function getInventoryDetails() {
        $sqlQuery = "SELECT p.pid, p.pname, p.model, p.quantity as product_quantity, s.quantity as recieved_quantity, r.total_shipped
            FROM " . $this->productTable . " as p
            LEFT JOIN " . $this->purchaseTable . " as s ON s.product_id = p.pid
            LEFT JOIN " . $this->orderTable . " as r ON r.product_id = p.pid ";
        if ( isset( $_POST[ 'order' ] ) ) {
            $sqlQuery .= 'ORDER BY ' . $_POST[ 'order' ][ '0' ][ 'column' ] . ' ' . $_POST[ 'order' ][ '0' ][ 'dir' ] . ' ';
        } else {
            $sqlQuery .= 'ORDER BY p.pid DESC ';
        }
        if ( $_POST[ 'length' ] != -1 ) {
            $sqlQuery .= 'LIMIT ' . $_POST[ 'start' ] . ', ' . $_POST[ 'length' ];
        }
        $result = mysqli_query( $this->dbConnect, $sqlQuery );
        $numRows = mysqli_num_rows( $result );
        $inventoryData = array();
        $i = 1;
        while ( $inventory = mysqli_fetch_assoc( $result ) ) {

            if ( !$inventory[ 'recieved_quantity' ] ) {
                $inventory[ 'recieved_quantity' ] = 0;
            }
            if ( !$inventory[ 'total_shipped' ] ) {
                $inventory[ 'total_shipped' ] = 0;
            }

            $inventoryInHand = ( $inventory[ 'product_quantity' ] + $inventory[ 'recieved_quantity' ] ) - $inventory[ 'total_shipped' ];

            $inventoryRow = array();
            $inventoryRow[] = $i++;
            $inventoryRow[] = "<div class='lh-1'><div>" . htmlspecialchars( $inventory[ 'pname' ], ENT_QUOTES ) . "</div><div class='fw-bolder text-muted'><small>" . htmlspecialchars( $inventory[ 'model' ], ENT_QUOTES ) . "</small></div></div>";
            $inventoryRow[] = $inventory[ 'product_quantity' ];
            $inventoryRow[] = $inventory[ 'recieved_quantity' ];
            $inventoryRow[] = $inventory[ 'total_shipped' ];
            $inventoryRow[] = $inventoryInHand;
            $inventoryData[] = $inventoryRow;
        }
        $output = array(
            "draw" => intval( $_POST[ "draw" ] ),
            "recordsTotal" => $numRows,
            "recordsFiltered" => $numRows,
            "data" => $inventoryData
        );
        echo json_encode( $output );
    }
}
?>