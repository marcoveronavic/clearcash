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


        .inputWrap input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
}

/* Make the entire inputWrap look clickable */
.inputWrap {
    cursor: pointer !important;
    border: 1px solid #ccc;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background-color 0.2s ease;
}
.inputWrap:hover {
    background-color: #f0f0f0;
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
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Select your period</h1>
                    <p>
                        Your budgeting and reporting will sync with this period.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    @if($errors->any())
                        <div class="flex flex-row alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('account-setup.step-one-store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="inputWrap">
                                    <input type="radio" name="period_selection" id="first_day_of_month" value="first_day">
                                    <div class="content">
                                        <h5>First to last day of the month</h5>
                                        <p>Your period will start on the 1st day of the month and end on the last.</p>
                                    </div>
                                    <div class="iconWrap">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>

                                <div class="inputWrap">
                                    <input type="radio" name="period_selection" id="last_working_day" value="last_working">
                                    <div class="content">
                                        <h5>Last working day of the month</h5>
                                        <p>Your period will reset on the last working day of the month.</p>
                                    </div>
                                    <div class="iconWrap">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>

                                <div class="inputWrap">
                                    <input type="radio" name="period_selection" id="fixed_monthly_date" value="fixed_date">
                                    <div class="content">
                                        <h5>Fixed Monthly Date</h5>
                                        <p>Your period will reset on the exact day you choose.</p>
                                    </div>
                                    <div class="iconWrap">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
document.addEventListener("DOMContentLoaded", function () {
    const inputWraps = document.querySelectorAll('.inputWrap');
    const form = document.querySelector('form');

    form.action = "{{ route('account-setup.step-one-store') }}";

    inputWraps.forEach(wrapper => {
        wrapper.addEventListener("click", function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;

                // Remove any previously added hidden inputs (to avoid duplicates)
                const oldHidden = form.querySelector('input[type="hidden"][name="period_selection"]');
                if (oldHidden) {
                    form.removeChild(oldHidden);
                }

                // Add hidden input with selected value
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = "period_selection";
                hiddenInput.value = radio.value;
                form.appendChild(hiddenInput);

                setTimeout(() => {
                    form.submit();
                }, 333);
            }
        });
    });
});
</script>

    {{-- <script>
        document.addEventListener("DOMContentLoaded", function () {
            const radioButtons = document.querySelectorAll('input[name="period_selection"]');
            const form = document.querySelector('form');

            form.action = "{{ route('account-setup.step-one-store') }}";

            radioButtons.forEach(radio => {
                radio.addEventListener("change", function () {
                    if (this.checked) {
                        console.log("Selected Value:", this.value); // Debugging
                        console.log("Submitting to:", form.action);

                        // Ensure the selected value is explicitly set in the form
                        const hiddenInput = document.createElement("input");
                        hiddenInput.type = "hidden";
                        hiddenInput.name = "period_selection";
                        hiddenInput.value = this.value;
                        form.appendChild(hiddenInput);

                        setTimeout(() => {
                            form.submit();
                        }, 333); // Delay of 333ms
                    }
                });
            });
        });

    </script> --}}

@endsection
