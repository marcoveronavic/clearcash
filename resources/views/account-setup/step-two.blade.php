@extends('layouts.customer')
@section('styles_in_head')
    {{-- Add your link below --}}
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header,
        aside.sidebar {
            display: none;
        }
        main.dashboardMain {
            padding-top: 2rem;
            width: 100%;
        }
        main.dashboardMain.full {
            padding-top: 2rem;
        }
    </style>
    <section class="setupStepsWrapper">
        <div class="container">
                        <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Add your investments and pensions</div>
                            <div class="sep"></div>
                            <div class="item">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4 px-0">
                {{-- <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div> --}}
                <div class="mt-md-4 mt-0">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <h4>
                            @if($accSetup['period_selection'] == 'first_day')
                                First to last day of the month
                            @elseif($accSetup['period_selection'] == 'last_working')
                                Last Working Day of the month
                            @elseif($accSetup['period_selection'] == 'fixed_date')
                                Fixed Monthly Date
                            @endif
                        </h4>
                        <h1>Select the date</h1>
                        <p>
                            Choose the day of the month you would like your budgeting and reporting period to start.
                        </p>
                    </div>
                </div>
                @if($errors->any())
                    <section class="errorsBanner">
                        <div class="container">
                            <div class="row">
                                <div class="col-12">
                                    <ul>
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                <div class="my-2 px-0">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1 pe-0">
                        <form action="{{ route('account-setup-step-two-store') }}" method="post">
                            @csrf
                            <div class="dateSelectMainWrapper mb-4">


                                    <div class="input-group singleDateSelect">

                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                            <div class="input-group-text">
                                                <input type="radio" class="btn-check" name="date" value="<?= $i ?>" id="<?= $i ?>" autocomplete="off">
                                                <label class="" for="<?= $i ?>"><?= $i ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                <p>
                                    Your period will reset on the <strong>Date Selected</strong>
                                </p>
                            </div>
                            <div class="row align-items-center my-4">
                                <div class="col-6 d-flex justify-content-start">
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
                                </div>
                                <div class="col-6 d-flex justify-content-end">
                                    <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row my-4">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">

                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $('input[name="date"]').change(function () {
                let selectedDate = $(this).val();
                let formattedDate = formatDateWithSuffix(selectedDate);
                $('p:contains("Your period will reset on the") strong').text(formattedDate);
            });

            function formatDateWithSuffix(day) {
                let suffix = "th";
                if (day % 10 == 1 && day != 11) suffix = "st";
                else if (day % 10 == 2 && day != 12) suffix = "nd";
                else if (day % 10 == 3 && day != 13) suffix = "rd";

                return `${day}${suffix} of each month`;
            }
        });
    </script>
@endsection
