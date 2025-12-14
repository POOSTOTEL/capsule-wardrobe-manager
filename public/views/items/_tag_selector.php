<?php



?>

<div class="tag-selector-component mb-4">
    <label for="tag-selector-input" class="form-label">Теги</label>

    
    <input type="hidden"
           name="<?= htmlspecialchars($name ?? 'tag_ids') ?>"
           id="tag-ids-input"
           value="<?= htmlspecialchars(implode(',', $selectedTags ?? [])) ?>">

    
    <div id="tag-selector-container"></div>

    
    <div class="popular-tags mt-3" style="display: none;">
        <small class="text-muted mb-2 d-block">Популярные теги:</small>
        <div id="popular-tags-container"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        TagManager.init().then(() => {
            
            const container = document.getElementById('tag-selector-container');
            const hiddenInput = document.getElementById('tag-ids-input');

            const tagSelector = new TagSelector({
                element: container,
                maxTags: <?= $maxTags ?? 10 ?>,
                allowCreate: <?= json_encode($allowCreate ?? true) ?>,
                onChange: function(selectedTags) {
                    
                    const tagIds = selectedTags.map(tag => tag.id).join(',');
                    hiddenInput.value = tagIds;
                }
            });

            
            const initialTagIds = hiddenInput.value
                .split(',')
                .filter(id => id.trim() !== '')
                .map(id => parseInt(id));

            if (initialTagIds.length > 0) {
                tagSelector.setSelectedTags(initialTagIds);
            }

            
            loadPopularTags(tagSelector);
        });

        
        async function loadPopularTags(tagSelector) {
            const popularTags = await TagManager.getPopularTags(5);

            if (popularTags.length > 0) {
                const container = document.getElementById('popular-tags-container');
                const section = container.parentElement.parentElement;

                
                section.style.display = 'block';

                
                popularTags.forEach(tag => {
                    const tagElement = document.createElement('span');
                    tagElement.className = 'popular-tag';
                    tagElement.style.backgroundColor = tag.color;
                    tagElement.style.color = getContrastColor(tag.color);
                    tagElement.innerHTML = `
                    ${tag.name}
                    <span class="tag-count">${tag.usage_count || 0}</span>
                `;

                    tagElement.addEventListener('click', () => {
                        tagSelector.selectTag(tag.id);
                    });

                    container.appendChild(tagElement);
                });
            }
        }

        
        function getContrastColor(hexColor) {
            hexColor = hexColor.replace('#', '');

            if (hexColor.length === 3) {
                hexColor = hexColor[0] + hexColor[0] + hexColor[1] + hexColor[1] + hexColor[2] + hexColor[2];
            }

            const r = parseInt(hexColor.substr(0, 2), 16);
            const g = parseInt(hexColor.substr(2, 2), 16);
            const b = parseInt(hexColor.substr(4, 2), 16);

            const brightness = (r * 299 + g * 587 + b * 114) / 1000;

            return brightness > 128 ? '#000000' : '#FFFFFF';
        }
    });
</script>