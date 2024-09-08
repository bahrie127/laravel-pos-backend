{{-- html daily report --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Daily Report</h1>

    <table>
        <tr>
            <th>Total Revenue</th>
            <td>{{ $dataReport['total_revenue'] }}</td>
        </tr>
        <tr>
            <th>Total Sold Quantity</th>
            <td>{{ $dataReport['total_sold_quantity'] }}</td>
        </tr>
    </table>

    <h2>Product Sold</h2>
    <table>
        <tr>
            <th>Product Name</th>
            <th>Price</th>
            <th>Total Quantity</th>
            <th>Total Price</th>
        </tr>
        @foreach ($totalProductSold as $product)
            <tr>
                <td>{{ $product->product_name }}</td>
                <td>{{ $product->product_price }}</td>
                <td>{{ $product->total_quantity }}</td>
                <td>{{ $product->total_price }}</td>
            </tr>
        @endforeach
</body>

</html>
{{-- html daily report --}}
