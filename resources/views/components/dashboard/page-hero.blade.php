<section class="dashboardPageHero">
    <div class="container-fluid">
        @if($displayButton == 'Yes' || $displayButton == 'yes')
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1>{{ $title }}</h1>
                </div>
                <div class="col-lg-4 d-lg-flex justify-content-lg-end">
                    <a href="{{ $buttonLink }}" class="primaryOutlineBtn">{{ $buttonContent }}</a>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-12">
                    <h1>{{ $title }}</h1>
                </div>
            </div>
        @endif
    </div>
</section>
