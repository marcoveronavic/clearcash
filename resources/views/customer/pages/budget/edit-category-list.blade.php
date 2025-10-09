@extends('layouts.customer')
@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Edit Full Budget</h1>
                </div>
            </div>
        </div>
    </section>

    <x-forms.form-main-errors/>

    <section class="editCategoryBudgetWrapper">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="inner">
                        <form action="{{ route('budget.update-category-list') }}" method="post">
                            @csrf
                            @method('put')
                            @foreach($budgetItems as $item)
                                <div class="editItem">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control mb-2"
                                                   name="budget_items[{{ $item->id }}][category_name]"
                                                   value="{{ str_replace('_', ' ', $item->category_name) }}" required>
                                            <input type="hidden" name="budget_items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                        </div>
                                        <div class="col-md-4 d-md-flex justify-content-md-end">
                                            <div class="input-group">
                                                <label for="" class="input-group-text">£</label>
                                                <input type="number" class="form-control" min="0" step="any"
                                                       name="budget_items[{{ $item->id }}][amount]"
                                                       value="{{ $item->amount }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Add New Budget Items --}}
                            <div id="newBudgetItems"></div>

                            <div class="row">
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" id="addBudgetItem" class="primaryOutlineBtn addNewItemBtn mt-3 block">+</button>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="twoToneBlueGreenBtn">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('addBudgetItem').addEventListener('click', function () {
            let newItemIndex = document.querySelectorAll('.newItem').length;
            let newItemHtml = `
            <div class="editItem newItem mt-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="new_items[${newItemIndex}][category_name]" placeholder="Category Name" required>
                    </div>
                    <div class="col-md-4 d-md-flex justify-content-md-end">
                        <div class="input-group">
                            <label for="" class="input-group-text">£</label>
                            <input type="number" class="form-control" min="0" step="any" name="new_items[${newItemIndex}][amount]" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
            </div>`;
            document.getElementById('newBudgetItems').insertAdjacentHTML('beforeend', newItemHtml);
        });
    </script>
@endsection
    