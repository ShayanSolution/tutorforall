<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>welcome email</title>
</head>
<body>

<table style="margin: 50px" width="350px" border="1px">
    <tr>
        <th>Number</th>
        <th colspan="2"><input style="width: 350px" type="number" id="number"></th>
        <th><button id="go">Search</button></th>
    </tr>
</table>

<table id="numbers_listing_table" style="margin: 50px" width="350px" border="1px">
    <thead>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Phone Number</th>
            <th>Role</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

</body>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $("#go").click(function(e) {
        var number = $("#number"). val();
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: "/number/list/",
            data: {
                number: number, // < note use of 'this' here
            },
            success: function(result) {
                $('#numbers_listing_table > tbody').append(result);
            },
            error: function(result) {
                alert('error');
            }
        });
    });


    function deleteNumber(id){
        $.ajax({
            type: "GET",
            url: "/number/delete/",
            data: {
                id: id, // < note use of 'this' here
            },
            success: function(result) {
                $("#user_row_"+id).remove();
                alert("Deleted Successfully");
            },
            error: function(result) {
                alert('error');
            }
        });
    }

</script>
</html>
