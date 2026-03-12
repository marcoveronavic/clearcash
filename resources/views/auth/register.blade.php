<div class="row">
    <div class="col-12">
        <label for="country">Paese</label>
        <select id="country" name="country" class="form-control @error('country') is-invalid @enderror" required>
            <option value="">-- Seleziona --</option>
            <option value="GB" {{ old('country') === 'GB' ? 'selected' : '' }}>🇬🇧 Regno Unito (GBP)</option>
            <option value="EU" {{ old('country') === 'EU' ? 'selected' : '' }}>🇪🇺 Europa (EUR)</option>
        </select>
        @error('country')
        <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
</div>
