<label for="{{ $name }}">{{ $label }} @if($required == 'true') <span>*</span> @endif</label>
<input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" class="form-control @error($name) is-invalid @enderror" value="{{ old($name, $value) }}" @if($required == 'true') required @endif>
<x-forms.form-field-errors name="{{ $name }}"/>
