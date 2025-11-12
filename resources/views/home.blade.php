@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <img src="https://github.com/ellaisys/aws-cognito/raw/master/assets/images/banner.png"
                        width="100%" alt="EllaiSys AWS Cloud Capability"/>

                    <h2><strong>Welcome: {{ __('You are logged in!') }}</strong></h2>
                    <h4>This is a demo application, that uses the Laravel Package to manage Web and API authentication with AWS Cognito</h4>

                    </br>
                    <h2><strong>Session Parameters:</strong></h2>
                    @if ($sessionData = session()->all())
                        <table class="table table-bordered table-striped">
                            <th>
                                <thead class="dark">
                                    <tr>
                                        <td style="width: 30%;">Key</td>
                                        <td>Value</td>
                                    </tr>
                                </thead>
                            </th>
                            <tbody>
                            @foreach($sessionData as $key=>$value)
                                <tr>
                                    <td style="word-break: break-word;">{{ $key }}</td>
                                    <td style="word-break: break-word;">{{ json_encode($value, JSON_UNESCAPED_UNICODE)}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
