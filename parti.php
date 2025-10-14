<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    $nomparti = htmlspecialchars(trim($_POST["nomparti"] ?? ""));
    $sigleparti = strtoupper(htmlspecialchars(trim($_POST["sigleparti"] ?? "")));

    if (isset($_FILES["logo"])) {
        $logo = file_get_contents($_FILES["logo"]['tmp_name']);
        $mime = mime_content_type($_FILES["logo"]['tmp_name']);

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $allowedMimeTypes = ['image/jpeg', 'image/png'];

        $fileName = $_FILES['logo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions) || !in_array($mime, $allowedMimeTypes)) {
            echo json_encode([
                'success' => false,
                'message' => "Fichier non autorisé. Seuls les fichiers JPG, JPEG et PNG sont acceptés."
            ]);
            exit;
        }
    }
    switch ($action) {
        // Traitement pour afficher les partis
        case "afficher":
            try {
                $stmt = $pdo->prepare("SELECT * FROM parti");
                $stmt->execute();
                $partis = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($partis as &$parti) {
                    if (!empty($parti["logo"])) {
                        $parti["logo"] = 'data:image/*;base64,' . base64_encode($parti["logo"]);
                    } else {
                        $parti["logo"] = null;
                    }
                }

                echo json_encode([
                    "data" => $partis
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur SQL : " . $e->getMessage()
                ]);
            }
            break;
        // Traitement pour ajouter les partis
        case "ajouter":
            if (isset($_FILES["logo"])) {
                if (empty($nomparti) || empty($sigleparti) || empty($logo)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Veuillez remplir tout les chants svp"
                    ]);
                    exit;
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM parti WHERE nomparti = :nomparti");
                        $verrify->execute(
                            array(
                                'nomparti' => $nomparti
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "parti existe déjà"
                            ]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO parti (nomparti, sigle, mime_type, logo) VALUES (:nomparti, :sigle, :mime, :logo)");
                            $stmt->execute(
                                array(
                                    "nomparti" => $nomparti,
                                    "sigle" => $sigleparti,
                                    "mime" => $mime,
                                    "logo" => $logo
                                )
                            );
                            echo json_encode([
                                "success" => true,
                                "message" => "Département ajouté avec succès"
                            ]);
                        }
                    } catch (PDOException $e) {
                        echo "Erreur de connexion : " . $e->getMessage();
                    }
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Aucune donnée réçue ou tous les champs ne sont pas remplis"
                ]);
            }
            break;

        // Traitement pour modifier les partis
        case "modifier":

            if (isset($_POST["id_parti"])) {
                $id_parti = htmlspecialchars(trim($_POST["id_parti"]));

                if (empty($nomparti) || empty($sigleparti)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Veuillez remplir tous les champs svp! ...."
                    ]);
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM parti WHERE nomparti = :nomparti AND id_parti != :id");
                        $verrify->execute(
                            array(
                                'nomparti' => $nomparti,
                                "id" => $id_parti
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "parti existe déjà"
                            ]);
                        } else {
                            if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {
                                $stmt = $pdo->prepare("UPDATE parti SET nomparti = :nomparti, sigle = :sigle, mime_type = :mime_type, logo = :logo WHERE id_parti = :id");
                                $stmt->execute(
                                    array(
                                        "nomparti" => $nomparti,
                                        "sigle" => $sigleparti,
                                        "mime_type" => $mime_type,
                                        "logo" => $logo,
                                        "id" => $id_parti
                                    )
                                );
                                echo json_encode([
                                    "success" => true,
                                    "message" => "Partis modifié avec succès"
                                ]);
                            } else {
                                $stmt = $pdo->prepare("UPDATE parti SET nomparti = :nomparti, sigle = :sigle WHERE id_parti = :id");
                                $stmt->execute(
                                    array(
                                        "nomparti" => $nomparti,
                                        "sigle" => $sigleparti,
                                        "id" => $id_parti
                                    )
                                );
                                echo json_encode([
                                    "success" => true,
                                    "message" => "Partis modifié avec succès"
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
                    "success" => false,
                    "message" => "Id maquant pour l'action"
                ]);
            }
            break;

        // Traitement pour supprimer les partis
        case "supprimer":
            if (isset($_POST["id"])) {
                $id = htmlspecialchars(trim($_POST["id"]));
                try {
                    $stmt = $pdo->prepare("DELETE FROM parti WHERE id_parti = :id");
                    $stmt->execute(
                        array(
                            "id" => $id
                        )
                    );
                    echo json_encode([
                        "success" => true,
                        "message" => "Parti supprimé avec succès"
                    ]);
                } catch (PDOException $e) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Erreur de connexion : " . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Id manquant pour la suppression"
                ]);
            }
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Donnée non reçue ou action manquante"
    ]);
}
