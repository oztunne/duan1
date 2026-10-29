(() => {
    const kind = document.getElementById('kind');
    const category = document.getElementById('category');
    if (!kind || !category) return;
    const updateCategories = () => {
        const allowed = kind.value === 'property' ? ['rent', 'buy'] : ['fulltime', 'parttime'];
        for (const option of category.options) {
            option.disabled = !allowed.includes(option.value);
            option.hidden = option.disabled;
        }
        if (!allowed.includes(category.value)) category.value = allowed[0];
    };
    kind.addEventListener('change', updateCategories);
    updateCategories();
})();
