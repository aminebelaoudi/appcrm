<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors *">
    <title>Mes inscriptions</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-container {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #e31c23;
            box-shadow: 0 0 0 3px rgba(227, 28, 35, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }

        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            font-size: 20px;
            cursor: pointer;
            display: none;
        }

        .clear-search:hover {
            color: #e31c23;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .custom-select-wrapper {
            position: relative;
            min-width: 200px;
        }

        .custom-select {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background: linear-gradient(to bottom, white, #fafafa);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-select:hover {
            border-color: #e31c23;
            box-shadow: 0 2px 8px rgba(227, 28, 35, 0.1);
        }

        .custom-select.open {
            border-color: #e31c23;
            box-shadow: 0 0 0 4px rgba(227, 28, 35, 0.1);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .select-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 10px;
            pointer-events: none;
            transition: all 0.3s;
        }

        .custom-select:hover ~ .select-arrow,
        .custom-select.open ~ .select-arrow {
            color: #e31c23;
        }

        .custom-select.open ~ .select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e31c23;
            border-top: none;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .select-dropdown.open {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .select-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select-option:hover {
            background: linear-gradient(to right, #fff5f5, #ffe8e8);
            color: #e31c23;
            padding-left: 20px;
        }

        .select-option.selected {
            background: #e31c23;
            color: white;
            font-weight: 600;
        }

        .select-option.selected:hover {
            background: #c71a1f;
        }

        .select-option::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: transparent;
        }

        .select-option.selected::before {
            background: white;
        }


        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            padding: 10px;
        }

        .property-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s, border 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            border-color: #e31c23;
        }

        .image-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background-color: #e0e0e0;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status-badge-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            color: #333;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge-overlay::before {
            content: '●';
            font-size: 12px;
            line-height: 1;
        }

        .status-sold::before {
            color: #4caf50;
        }

        .status-active-overlay::before {
            color: #2196f3;
        }

        .status-pending::before {
            color: #ff9800;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 20px;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(0,0,0,0.8);
        }

        .carousel-prev {
            left: 10px;
        }

        .carousel-next {
            right: 10px;
        }

        .carousel-image {
            display: none;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-image.active {
            display: block;
        }

        .carousel-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.3s;
        }

        .dot.active {
            background: rgba(255, 255, 255, 1);
        }

        .property-info {
            padding: 20px;
        }

        .price {
            color: #e31c23;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .address {
            font-size: 16px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .property-type {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .features {
            display: flex;
            gap: 12px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1a1a1a;
            font-size: 13px;
            font-weight: 600;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 8px 14px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .feature img {
            transition: transform 0.3s ease;
        }


        .feature svg {
            width: 24px;
            height: 24px;
            fill: #333;
        }

        .no-properties {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 18px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #e31c23;
            color: white;
            border-color: #e31c23;
        }

        .pagination .active {
            background-color: #e31c23;
            color: white;
            border-color: #e31c23;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- <h2>Mes inscriptions</h2> -->
        
        <!-- Barre de recherche et filtres -->
        <div class="filters-bar">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="Rechercher par adresse, ville..."
                       onkeyup="filterProperties()">
                <button class="clear-search" id="clearSearch" onclick="clearSearch()">×</button>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select" onclick="toggleDropdown()" id="customSelect">
                        <span id="selectedText">Tous les statuts</span>
                    </div>
                    <span class="select-arrow">▼</span>
                    <div class="select-dropdown" id="selectDropdown">
                        <div class="select-option selected" data-value="" onclick="selectOption('', 'Tous les statuts')">
                            Tous les statuts
                        </div>
                        <div class="select-option" data-value="active" onclick="selectOption('active', 'En vigueur')">
                            En vigueur
                        </div>
                        <div class="select-option" data-value="sold" onclick="selectOption('sold', 'Vendu')">
                            Vendu
                        </div>
                        <div class="select-option" data-value="pending" onclick="selectOption('pending', 'Hors marché')">
                            Hors marché
                        </div>
                    </div>
                </div>
            </div>
        
        </div>
        
        @if(count($properties) > 0)
            <div class="properties-grid">
                @foreach($properties as $property)
                    <div class="property-card" data-property-id="{{ $property['ListingKey'] ?? '' }}" onclick="window.location.href='{{ route('centris.property.details', ['listingKey' => $property['ListingKey'] ?? '', 'locationId' => $locationId]) }}'" >
                        <div class="image-container">
                            @if(isset($property['Media']) && count($property['Media']) > 0)
                                @foreach($property['Media'] as $index => $media)
                                    <img src="{{ $media['MediaURL'] ?? '' }}" 
                                         alt="{{ $media['ImageOf'] ?? 'Propriété' }}" 
                                         class="carousel-image {{ $index === 0 ? 'active' : '' }}"
                                         data-index="{{ $index }}">
                                @endforeach
                                @if(count($property['Media']) > 1)
                                    <button class="carousel-nav carousel-prev" onclick="event.stopPropagation(); changeImage(this, -1)">‹</button>
                                    <button class="carousel-nav carousel-next" onclick="event.stopPropagation(); changeImage(this, 1)">›</button>
                                    <div class="carousel-dots">
                                        @foreach($property['Media'] as $index => $media)
                                            <span class="dot {{ $index === 0 ? 'active' : '' }}" onclick="event.stopPropagation(); goToImage(this, {{ $index }})"></span>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <img src="https://via.placeholder.com/400x250?text=Aucune+Image" alt="Aucune image" class="carousel-image active">
                            @endif
                            
                            @php
                                $status = strtolower($property['MlsStatus'] ?? 'active');
                                $statusClass = 'status-active-overlay';
                                $statusText = 'En vigueur';
                                
                                if (in_array($status, ['sold', 'vendue', 'vendu'])) {
                                    $statusClass = 'status-sold';
                                    $statusText = 'Vendu';
                                } elseif (in_array($status, ['pending', 'en attente'])) {
                                    $statusClass = 'status-pending';
                                    $statusText = 'Hors marché';
                                } elseif ($status === 'active') {
                                    $statusText = 'En vigueur';
                                }
                            @endphp
                            <div class="status-badge-overlay {{ $statusClass }}">{{ $statusText }}</div>
                        </div>
                        
                        <div class="property-info">                    
                            <div class="address">      
                                {{ $property['StreetNumberStart'] ?? '' }}
                                @if(isset($property['StreetNumberEnd']))
                                    -{{ $property['StreetNumberEnd'] }}
                                @endif
                                , {{ $property['StreetShortName'] ?? '' }}
                            </div>
                         
                            <div class="features">
                                <div class="feature">
                                    <img src="{{ asset('img/contact.svg') }}" alt="Contacts" style="width: 18px; height: 18px; filter: brightness(0) opacity(0.7);">
                                    <span>{{ $property['PersonsCount'] ?? 0 }}</span>
                                </div>
                                
                                <div class="feature">
                                    <img src="{{ asset('img/opportunity.svg') }}" alt="Opportunités" style="width: 18px; height: 18px; filter: brightness(0) opacity(0.7);">
                                    <span>{{ $property['OpportunitiesCount'] ?? 0 }} Opp</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-properties">
                Aucune propriété disponible pour le moment.
            </div>
        @endif

        @if(isset($pagination) && $pagination['total_pages'] > 1)
            <div class="pagination">
                @if($pagination['has_prev'])
                    <a href="?locationId={{ $locationId }}&page={{ $pagination['current_page'] - 1 }}">‹ Précédent</a>
                @else
                    <span class="disabled">‹ Précédent</span>
                @endif

                @for($i = 1; $i <= $pagination['total_pages']; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="active">{{ $i }}</span>
                    @else
                        <a href="?locationId={{ $locationId }}&page={{ $i }}">{{ $i }}</a>
                    @endif
                @endfor

                @if($pagination['has_next'])
                    <a href="?locationId={{ $locationId }}&page={{ $pagination['current_page'] + 1 }}">Suivant ›</a>
                @else
                    <span class="disabled">Suivant ›</span>
                @endif
            </div>
            <div class="pagination-info">
                Affichage de {{ (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 }} à {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count']) }} sur {{ $pagination['total_count'] }} propriétés
            </div>
        @endif
    </div>

    <script>
        let currentStatusFilter = '';

        function toggleDropdown() {
            const select = document.getElementById('customSelect');
            const dropdown = document.getElementById('selectDropdown');
            
            select.classList.toggle('open');
            dropdown.classList.toggle('open');
        }

        function selectOption(value, text) {
            // Mettre à jour l'affichage
            document.getElementById('selectedText').textContent = text;
            
            // Mettre à jour la sélection visuelle
            document.querySelectorAll('.select-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // Fermer le dropdown
            document.getElementById('customSelect').classList.remove('open');
            document.getElementById('selectDropdown').classList.remove('open');
            
            // Mettre à jour le filtre et appliquer
            currentStatusFilter = value;
            filterProperties();
        }

        // Fermer le dropdown si on clique ailleurs
        document.addEventListener('click', function(event) {
            const wrapper = event.target.closest('.custom-select-wrapper');
            if (!wrapper) {
                document.getElementById('customSelect')?.classList.remove('open');
                document.getElementById('selectDropdown')?.classList.remove('open');
            }
        });

        function filterProperties() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.property-card');
            let visibleCount = 0;
            
            // Afficher/masquer le bouton clear
            const clearBtn = document.getElementById('clearSearch');
            clearBtn.style.display = searchValue ? 'block' : 'none';
            
            cards.forEach(card => {
                const address = card.querySelector('.address')?.textContent.toLowerCase() || '';
                const statusBadge = card.querySelector('.status-badge-overlay')?.textContent.toLowerCase() || '';
                
                // Convertir le statut badge en valeur de filtre
                let cardStatus = '';
                if (statusBadge.includes('vendu')) cardStatus = 'sold';
                else if (statusBadge.includes('hors marché') || statusBadge.includes('hors marche')) cardStatus = 'pending';
                else if (statusBadge.includes('en vigueur')) cardStatus = 'active';
                
                const matchesSearch = !searchValue || address.includes(searchValue);
                const matchesStatus = !currentStatusFilter || cardStatus === currentStatusFilter;
                
                if (matchesSearch && matchesStatus) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filterProperties();
        }

        function changeImage(button, direction) {
            const card = button.closest('.property-card');
            const images = card.querySelectorAll('.carousel-image');
            const dots = card.querySelectorAll('.dot');
            let currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
            
            // Retirer la classe active de l'image et du point actuels
            images[currentIndex].classList.remove('active');
            if (dots[currentIndex]) {
                dots[currentIndex].classList.remove('active');
            }
            
            // Calculer le nouvel index
            currentIndex = (currentIndex + direction + images.length) % images.length;
            
            // Ajouter la classe active à la nouvelle image et au point
            images[currentIndex].classList.add('active');
            if (dots[currentIndex]) {
                dots[currentIndex].classList.add('active');
            }
        }
        
        function goToImage(dotElement, index) {
            const card = dotElement.closest('.property-card');
            const images = card.querySelectorAll('.carousel-image');
            const dots = card.querySelectorAll('.dot');
            
            // Retirer toutes les classes active
            images.forEach(img => img.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Ajouter la classe active à l'image et au point sélectionnés
            images[index].classList.add('active');
            dots[index].classList.add('active');
        }
    </script>
</body>
</html>
