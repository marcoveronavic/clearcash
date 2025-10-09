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
