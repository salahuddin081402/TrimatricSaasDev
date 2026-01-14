<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h4 class="mb-0">Company List</h4>
                        </div>

                        <div class="card-body">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                <tr>
                                    <th width="60">SL#</th>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Contact No</th>
                                    <th width="120">Action</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($companies as $key => $company)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $company->name }}</td>
                                        <td>{{ $company->address }}</td>
                                        <td>{{ $company->contact_no }}</td>
                                        <td>
                                            <a href="{{ route('company.login', $company->slug) }}"
                                            class="btn btn-sm btn-primary">
                                                Login
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
