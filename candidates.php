<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["action"]) {
    $action = $_POST["action"];
    $nom = strtoupper(htmlspecialchars(trim($_POST["last_name"])));
    $prenom = htmlspecialchars(trim($_POST["first_name"]));
    $datenaiss = htmlspecialchars(trim($_POST["birthday"]));
    $nomparti = htmlspecialchars(trim($_POST["politicalParty"]));

    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        $mime_type = mime_content_type($_FILES["photo"]['tmp_name']);
        $photo = file_get_contents($_FILES["photo"]['tmp_name']);

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $allowedMimeTypes = ['image/jpeg', 'image/png'];

        $fileName = $_FILES['photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions) || !in_array($mime_type, $allowedMimeTypes)) {
            echo json_encode([
                'success' => false,
                'message' => "Fichier non autorisé. Seuls les fichiers JPG, JPEG et PNG sont acceptés."
            ]);
            exit;
        }
    }

    switch ($action) {
        // Traitement pour afficher les candidats
        case "afficher":
            try {
                $stmt = $pdo->prepare("SELECT candidates.*, parti.sigle FROM candidates INNER JOIN parti ON candidates.nomparti = parti.nomparti");
                $stmt->execute();
                $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($candidates as &$candidat) {
                    if (!empty($candidat["photo"]) && !empty($candidat["mime_type"])) {
                        $candidat["photo"] = 'data:' . $candidat["mime_type"] . ';base64,' . base64_encode($candidat["photo"]);
                    } else {
                        $candidat["photo"] = null;
                    }
                }

                echo json_encode([
                    "data" => $candidates
                ]);
            } catch (PDOException $e) {
                echo "erreur de connexion : " . $e->getMessage();
            }
            break;

        // Traitement pour supprimer les candidats
        case "supprimer":
            if (isset($_POST["id"])) {
                $id = htmlspecialchars(trim($_POST["id"]));
                try {
                    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id_candidat = :id_candidat");
                    $stmt->execute(
                        [
                            "id_candidat" => $id
                        ]
                    );
                    echo json_encode([
                        'success' => true,
                        'message' => "Candidat supprimé avec succès"
                    ]);
                } catch (PDOException $e) {
                    echo "Erreur de connexion : " . $e->getMessage();
                }
            } else {
                echo json_encode([
                    'success' => false,
                    "message" => "Id manquant pour éffectuer l'action"
                ]);
            }
            break;

        // Traitement pour ajouter les candidats
        case "ajouter":
            if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {

                if (empty($nom) || empty($prenom) || empty($datenaiss) || empty($nomparti) || empty($photo)) {
                    echo json_encode([
                        'success' => false,
                        "message" => "Veuillez remplir tout les chants svp"
                    ]);
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE nomparti = :nomparti ");
                        $verrify->execute(
                            array(
                                'nomparti' => $nomparti,
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "Candidat existe déjà pour cette parti"
                            ]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO candidates (nom, prenom, datenaiss, nomparti, mime_type, photo) VALUES (:nom, :prenom, :datenaiss, :nomparti, :mime_type, :photo)");
                            $stmt->execute(
                                [
                                    "nom" => $nom,
                                    "prenom" => $prenom,
                                    "datenaiss" => $datenaiss,
                                    "nomparti" => $nomparti,
                                    "mime_type" => $mime_type,
                                    "photo" => $photo,
                                ]
                            );
                            echo json_encode([
                                'success' => true,
                                'message' => "Candidat ajouté avec succès"
                            ]);
                        }
                    } catch (PDOException $e) {
                        echo "Erreur de connexion : " . $e->getMessage();
                    }
                }
            } else {
                echo json_encode([
                    'sucsess' => false,
                    "message" => "Aucune donnée reçue ou le téléchargement de la photo a échoué"
                ]);
            }
            break;

        // Traitement pour modifier les candidats
        case "modifier":
            if (isset($_POST["id_candidat"])) {
                $id_candidat = htmlspecialchars(trim($_POST["id_candidat"]));

                if (empty($nom) || empty($prenom) || empty($datenaiss) || empty($nomparti)) {
                    echo json_encode([
                        'success' => false,
                        "message" => "Veuillez remplir tout les chants svp"
                    ]);
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE nomparti = :nomparti AND id_candidat != :id_candidat");
                        $verrify->execute(
                            array(
                                'nomparti' => $nomparti,
                                "id_candidat" => $id_candidat
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "Candidat existe déjà pour cette parti"
                            ]);
                        } else {
                            if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {

                                $stmt = $pdo->prepare("UPDATE candidates SET nom = :nom, prenom = :prenom, datenaiss = :datenaiss, nomparti = :nomparti, mime_type = :mime_type, photo = :photo WHERE id_candidat = :id_candidat");
                                $stmt->execute(
                                    [
                                        "nom" => $nom,
                                        "prenom" => $prenom,
                                        "datenaiss" => $datenaiss,
                                        "nomparti" => $nomparti,
                                        "mime_type" => $mime_type,
                                        "photo" => $photo,
                                        "id_candidat" => $id_candidat
                                    ]
                                );
                                echo json_encode([
                                    'success' => true,
                                    'message' => "Candidat modifié avec succès"
                                ]);
                            } else {
                                $stmt = $pdo->prepare("UPDATE candidates SET nom = :nom, prenom = :prenom, datenaiss = :datenaiss, nomparti = :nomparti WHERE id_candidat = :id_candidat");
                                $stmt->execute(
                                    [
                                        "nom" => $nom,
                                        "prenom" => $prenom,
                                        "datenaiss" => $datenaiss,
                                        "nomparti" => $nomparti,
                                        "id_candidat" => $id_candidat
                                    ]
                                );
                                echo json_encode([
                                    'success' => true,
                                    'message' => "Candidat modifié avec succès"
                                ]);
                            }
                        }
                    } catch (PDOException $e) {
                        echo json_encode([
                            "success" => false,
                            "message" => "Erreur de connexion" . $e->getMessage()
                        ]);
                    }
                }
            } else {
                echo json_encode([
                    'success' => false,
                    "message" => "Aucune donnée reçue ou des champs sont vide"
                ]);
            }
            break;
    }
}
