<?php // Caré

class User{

    private $_id;
    private $_login;
    private $_mdp;
    private $_prenom;
    private $_MonPersonnage;
    private $_admin;

    private $_bdd;

    public function __construct($bdd){
        $this->_bdd = $bdd;
    }
    public function setUser($id,$login,$mdp,$prenom,$admin){
        $this->_id = $id;
        $this->_login = $login;
        $this->_mdp = $mdp;
        $this->_prenom = $prenom;
        $this->_admin = $admin;
    }
    public function setUserById($id){
        $Result = $this->_bdd->query("SELECT * FROM `User` WHERE `id`='".$id."' ");
        if($tab = $Result->fetch()){ 
            $this->setUser($tab["id"],$tab["login"],$tab["mdp"],$tab["prenom"],$tab["admin"]);
            //chercher son personnage
            $personnage = new Personnage($this->_bdd);
            $personnage->setPersonnageById($tab["idPersonnage"]);
            $this->_MonPersonnage = $personnage;
        }
    }
    public function setPersonnage($Perso){
        $this->_MonPersonnage = $Perso;
        //je mémorise en base l'association du personnage dans user
        $req ="UPDATE `User` SET `idPersonnage`='".$Perso->getID()."' WHERE  `id` = '".$this->_id."'";
        $Result = $this->_bdd->query($req);
    }
    //retour true si c'est un admin
    public function isAdmin(){
        return $this->_admin;
    }
    public function getPrenom(){
        return $this->_prenom;
    }
    public function getId(){
        return $this->_id;
    }
    public function getNomPersonnage(){
        return $this->_MonPersonnage->getNom();
    }
    public function getPersonnage(){
        return $this->_MonPersonnage;
    }
    public function getAllMyMobIds(){
        $listMob=array();
        $req="SELECT `id` FROM `Entite` WHERE `idUser`   in (SELECT `id` FROM `Entite` WHERE `idUser` = '".$this->_id."') AND Type=2";
        $Result = $this->_bdd->query($req);
        while($tab=$Result->fetch()){
            array_push($listMob,$tab[0]);
        }
        return $listMob;
    }
    public function ConnectToi(){
        $errorMessage="";
        //si c'est une inscription on valide l'inscription et on le connect
        if( isset($_POST["sub"])){
            if($_POST['MDP'] == $_POST['password']) {
                if(!empty($_POST['prenom'])){
                    $req ="INSERT INTO `User`( `login`, `prenom`, `mdp`) VALUES ('".$_POST['login']."','".$_POST['prenom']."','".$_POST['password']."')";
                    $Result = $this->_bdd->query($req);
                }else{
                    $errorMessage = "Il faut un prénom à l'inscription.";
                }
            }else{
                echo "les mots de passes ne corespondes pas";
            }
            
        }


        //traitement du formulaire
        $access = false;
        if( isset($_POST["login"]) && isset($_POST["password"])){
            //verif mdp en BDD

            $Result = $this->_bdd->query("SELECT * FROM `User` WHERE `login`='".$_POST['login']."' AND `mdp` = '".$_POST['password']."'");
            if($tab = $Result->fetch()){ 

                $this->setUserById($tab["id"]);

                //si mdp = ok
                $access = true;
                $_SESSION["idUser"]= $tab["id"];
                $_SESSION["Connected"]=true;
                $afficheForm = false;
                //si on est co on affiche le formulaire de deco
                $this->DeconnectToi();
            }else{
                if ($errorMessage==""){
                    $errorMessage = "Votre login et mdp ne correspondent pas.";
                }
                $afficheForm = true;
            }

        }else{
            $afficheForm = true;
        }

        if($afficheForm){
        ?>
        <div class="formlogin">
            <?php
            if ($errorMessage!=""){
                echo '<div class="Red">'.$errorMessage.'</div>';
            }
            ?>
            <form action="" method="post" >
                <div>
                    <label for="login">Mail : </label>
                    <input type="email" name="login" id="login" required >
                </div>
                <div >
                    <label for="password">Password: </label>
                    <input type="password" name="password" id="password" required>
                </div>

                <div >
                    <label class="inscriptionHide logSub" for="prenom">Prénom si tu t'inscris : </label>
                    <input class="inscriptionHide logSub" type="text" name="prenom" id="prenom" >
                </div>

                <div >
                    <input type="submit" value="GO !" name="log" id="logSubsubmit"> <a class="inscriptionShow logSub" id="subCreatclick" onclick="inscription()">Inscription au jeu</a>
                </div>
            </form>
        </div>
        <script>
            function inscription(){
                var TabElements = document.getElementsByClassName("logSub");
                for (var e of TabElements) {
                    e.classList.add('inscriptionShow');
                    e.classList.remove('inscriptionHide');
                }
                document.getElementById("logSubsubmit").setAttribute("name", "sub"); 

                var e = document.getElementById("subCreatclick");  
                e.className = 'inscriptionHide';
            }
        </script>
        <?php
        }

        return $access;
    }
    public function DeconnectToi(){

        //traitement du formulaire
        $afficheForm = true;
        $access = true;
        if( isset($_POST["logout"]) && isset($_POST["logout"])){
            //si on se deco on raffiche le formulaire de co
            $_SESSION["Connected"]=false;
            session_unset();
            session_destroy();
            $this->ConnectToi();
            $afficheForm = false;
            $access = false;
        }else{
            $afficheForm = true;
        }

        if($afficheForm){
        ?>
            <form action="" method="post" >
                <div >
                    <input type="submit" value="Deco!" name="logout">
                </div>
            </form>
        <?php
        }
        return $access;
    }
    //retourne une carte de Div HTML de tracé de div
    public function getVisitesHTML($taille){
        //etape 1 récupéré toutes les visites du user
        $Map = $this->getPersonnage()->getMap();
        $maxX=$Map->getX()+$taille;
        $minX=$Map->getX()-$taille;
        $maxY=$Map->getY()+$taille;
        $minY=$Map->getY()-$taille;;

        if ($taille>0){
            $req="SELECT `map`.`id`,`map`.`x`,`map`.`y` 
            FROM `Visites`,`map` , `Entite`
            WHERE map.id = Visites.idMap 
            AND Visites.idPersonnage = Entite.id 
            AND `Entite`.`idUser`='".$this->_id."' 
            AND map.x >= '".$minX."' 
            AND map.x <= '".$maxX."' 
            AND map.y >= '".$minY."' 
            AND map.y <= '".$maxY."' 
            group by `Visites`.`idMap`";
        }else{
            $req="SELECT `map`.`id`,`map`.`x`,`map`.`y` 
            FROM `Visites`,`Entite`,`map` 
            WHERE map.id = Visites.idMap 
            AND Visites.idPersonnage = Entite.id 
            AND `Entite`.`idUser`='".$this->_id."' 
            group by `Visites`.`idMap`";
        }

        $Result = $this->_bdd->query($req);
        $allMap = array();

        while($visite = $Result->fetch()){
            //$allMap[x][y]=idmap
            if($visite['x'] > $maxX){
                $maxX = $visite['x'];
            }
            if($visite['x'] < $minX){
                $minX = $visite['x'];
            }
            if($visite['y'] > $maxY){
                $maxY = $visite['y'];
            }
            if($visite['y'] < $minY){
                $minY = $visite['y'];
            }

            $allMap[$visite['x']][$visite['y']]=$visite['id'];
        }

        $LargeurX = $maxX - $minX ;
        $HauteurY = $maxY - $minY ;

        ($LargeurX == 0)?$LargeurX =1:$LargeurX;

        $taille=200;

        $HY = $LX = round($taille/$LargeurX);
        $taille = $LX*$LargeurX;


        //permet de réadapter la taille en fonction de l'arondi qui a grossi les div

        $Map = $this->getPersonnage()->getMap();
        $MapScan = new Map($this->_bdd);

        $style = 'style="width:'.$taille.'px"';
        $styleCellule = 'style="width:'.$LX.'px;height:'.$HY.'px"';

        //On rajoute largeur de x pour laisser de la place à la border
        $ligneTaille = $LargeurX*$LX+$LargeurX*2;
        $styleLigne = 'style="width:'.$ligneTaille.'px;height:'.$HY.'px"';

        echo '<div class="map" '.$style.'>';
        for($y=$maxY;$y>$minY;$y--){

            echo '<div class="mapLigne" '.$styleLigne.'>';
            for($x=$minX;$x<$maxX;$x++){
               
                 if ($y==$Map->getY() && $x==$Map->getX()) {
                    echo '<div class="mapPositionUser" '.$styleCellule.'>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/26/Compass_Rose_French_North.svg/800px-Compass_Rose_French_North.svg.png" widht="'.$LX.'px" height="'.$LX.'px">
                    </div>';
                }else if($y==0 && $x==0){
                    echo '<div class="mapOrigine" '.$styleCellule.'></div>';
                }else{
                    if(array_key_exists($x,$allMap)){
                        if(array_key_exists($y,$allMap[$x])){
                            if(!is_null($allMap[$x][$y])){

                                //map found check it bro 
                                $MapScan->setMapByID($allMap[$x][$y]);

                                if(count($MapScan->getAllMobContre($this))){
                                    echo '<div class="mapMob" '.$styleCellule.'></div>';
                                }else if (count($MapScan->getAllMobCapture($this))){
                                    echo '<div class="mapClear" '.$styleCellule.'></div>';
                                }else{
                                    echo '<div class="mapVerte" '.$styleCellule.'></div>';
                                }
                            }else{
                                echo '<div class="mapRouge" '.$styleCellule.'></div>';
                            }
                        }else{
                            echo '<div class="mapRouge" '.$styleCellule.'></div>';
                        }
                    }else{
                        echo '<div class="mapRouge" '.$styleCellule.'></div>';
                    }
                }
            }
            echo '</div>';
        }
        echo '</div>';
    }
    //affiche tout les utilisateurs ainsi que leurs donnée (commande de préférance admin)
    public function showusers(){
        $all = $this->_bdd->query("SELECT * FROM user");
        $show = $all->fetch();

        echo $show['id'];
        echo $show['login'];
        echo $show['prenom'];
        echo $show['mdp'];
        echo $show['idPersonnage'];
        echo $show['admin'];

    }
    //fonction pour modifier un prenom en base
    public function updateuser(){
        $Up = $this->_bdd->query("UPDATE `user` SET `prenom`='".$POST['newprenom']."' WHERE id=".$this->_id." ");
            if($Up){
                echo "Ton prénom a bien été changé.";
            }else{
                echo "Une erreur est survenue :/";
            }
    }
    //fonction pour supprimé un utilisateur version admin
    public function deleteuseradminversion(){
        $Del = $this->_bdd->query("DELETE FROM user WHERE id= ".$_POST['id']."");
            if($Del){
                echo "utilisateur supprimé";
            }else{
                echo "une erreur est survenue";
            }
    }
    //fonction pour ajouté un utilisateur
    public function adduser(){
        //ajoute un commentaire dans la base de la page du jeu selectionné
        $add = $this->_bdd->query("INSERT INTO user (login, prenom, mdp, idPersonnage, admin) VALUES (".$_POST['login'].",".$_POST['prenom'].",".$_POST['mdp'].",".$_POST['idPersonnage'].", 0 ) ");
        if($add){
            echo "utilisateur ajouté .";
        } else {
            echo "Une erreur est survenue.";
        }
    }
    //fonction pour modifier un mot de passe
    public function updatepassword(){
        if (isset($_POST["updatemdp"])) {
            //comparaison du mot de passe avec l'ancien
            if($_POST['NEWMDP'] == $_POST['password']) {
                //mise a jour dans la base du nouveau mot de passe
                $rep = $this->_bdd->query("UPDATE `user` SET `mdp`='".$_POST['NEWMDP']."' WHERE id=".$this->_id." ");
                if($rep){
                    //succées 
                    echo "Mot de passe changé";
                }else{
                    //erreur a l'update dans la base
                    echo "Une erreur est survenue";
                }
            } else {
                //message d'erreur
                echo "les mots de passe ne correspondent pas...";
            }
        }
    }
    //fonction pour modifier un mot de passe version admin
    public function updatepasswordadminversion(){
        if (isset($_POST["updateusermdp"])) {
            //mise a jour dans la base du nouveau mot de passe
            $rep = $this->_bdd->query("UPDATE `user` SET `mdp`='".$_POST['NEWMDP']."' WHERE `id`='".$_POST['id']."' ");
            if($rep){
                //succées 
                echo "Le mot de passe de l'utilisateur a été changé";
            }else{
                //erreur a l'update dans la base
                echo "Une erreur est survenue";
            }
        }
    }
    
    //retourne normalement la faction du Joueur
    public function getFaction(){
        $req="SELECT Faction.id, Faction.nom 
            FROM `Faction` ,`Personnage`, `User` , `TypePersonnage` 
            WHERE User.idPersonnage = Personnage.id 
            AND Personnage.idTypePersonnage = Typepersonnage.id 
            AND Typepersonnage.idFaction = Faction.id 
            AND User.id = '".$this->_id."' ";
        $Result = $this->_bdd->query($req);
        if($tab=$Result->fetch()){
           $Faction = new Faction($this->_bdd);
           $Faction->setFactionById($tab['id']);
           return $Faction;
        }else{
            return null;
        }
    }
}
?>