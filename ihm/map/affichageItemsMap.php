<div class="MapItem">
    <?php
        $listItems = $map->getItems();
        if(count($listItems)>0){
            ?>
                <p class="pItem">Items Présent :</p>
                <p class="divRarete">Commun - Rare</p>
                <ul class="Item">
                    <?php
                        foreach($listItems as $Item){
                            ?>
                                <li id="item<?= $Item->getId() ?>" style="<?= $Item->getClassRarete() ?>">
                                    <a onclick="CallApiAddItemInSac(<?= $Item->getId() ?>)">
                                        <?= $Item->getNom() ?>
                                    </a>
                                </li>
                            <?php
                        }
                    ?>
                </ul>
            <?php
        }
    ?>
</div>