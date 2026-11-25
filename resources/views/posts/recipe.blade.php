@php
    $recipeRows = old('recipe', isset($post) ? $post->ingredients->map(fn($i) => ['ingredient_id' => $i->id, 'quantity' => $i->pivot->quantity, 'unit' => $i->pivot->unit])->all() : []);
    $recipeIngredients = $ingredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'stock' => $i->stock])->all();
    $recipeRows = array_values($recipeRows);
@endphp
<div class="mt-4 p-3 border rounded-3">
    <input type="hidden" name="expected_stock" value="{{ $post->stock ?? 0 }}">
    <h2 class="h6 fw-bold">Resep per 1 porsi / gelas</h2>
    <p class="small text-muted">Untuk tipe Resep (BoM), isi bahan dan takaran. Contoh: stok teh dalam liter, resep 200 ml; 2 gelas menghabiskan 0,4 liter. gram/kg dan ml/liter dikonversi otomatis.</p>
    @if($ingredients->isEmpty())<p class="text-danger">Tambahkan bahan baku terlebih dahulu di Master Bahan Baku.</p>@endif
    <div class="table-responsive"><table class="table"><thead><tr><th>Bahan</th><th>Takaran per porsi</th><th>Satuan resep</th><th></th></tr></thead><tbody id="recipeRows"></tbody></table></div>
    <button type="button" class="btn btn-light border btn-sm" id="addRecipe">+ Tambah bahan resep</button>
    <div class="mt-3"><label class="form-label" for="finishedStock">Stok barang jadi (hanya untuk tipe Barang Statis)</label><input id="finishedStock" type="number" min="0" step="1" name="stock" value="{{ old('stock', $post->stock ?? 0) }}" class="form-control" style="max-width:240px"></div>
</div>
@push('scripts')
<script>
(() => {
    const ingredients = @json($recipeIngredients);
    const initial = @json($recipeRows);
    let index = 0;
    const body = document.getElementById('recipeRows');
    function add(row = {}) {
        const key = index++;
        const tr = document.createElement('tr');
        const ingredientCell = tr.insertCell();
        const select = document.createElement('select'); select.className = 'form-select'; select.name = `recipe[${key}][ingredient_id]`; select.required = true;
        select.add(new Option('Pilih bahan', ''));
        ingredients.forEach(i => select.add(new Option(`${i.name} — stok ${i.stock} ${i.unit}`, i.id)));
        select.value = row.ingredient_id ?? ''; ingredientCell.append(select);
        const qty = document.createElement('input'); qty.type = 'number'; qty.min = '0.000001'; qty.max = '1000000'; qty.step = '0.000001'; qty.required = true; qty.className = 'form-control'; qty.name = `recipe[${key}][quantity]`; qty.value = row.quantity ?? ''; tr.insertCell().append(qty);
        const unit = document.createElement('input'); unit.className = 'form-control'; unit.required = true; unit.maxLength = 20; unit.name = `recipe[${key}][unit]`; unit.value = row.unit ?? ''; unit.placeholder = 'ml, liter, gram, kg, pcs'; tr.insertCell().append(unit);
        select.addEventListener('change', () => { const i = ingredients.find(i => String(i.id) === select.value); if (i) unit.value = i.unit; });
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-light text-danger'; remove.textContent = 'Hapus'; remove.onclick = () => tr.remove(); tr.insertCell().append(remove);
        body.append(tr);
    }
    initial.forEach(add);
    document.getElementById('addRecipe').onclick = () => add();
})();
</script>
@endpush
