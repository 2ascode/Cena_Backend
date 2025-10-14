<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data["action"])) {
    $action = $data["action"];

    switch ($action) {
        // Traitement pour afficher les départements
        case "afficher":
            try {
                $stmt = $pdo->prepare("SELECT * FROM dpatement");
                $stmt->execute();
                $departements = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    "data" => $departements
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur SQL : " . $e->getMessage()
                ]);
            }
            break;

        // Traitement pour ajouter les départements
        case "ajouter":
            if (isset($data["nomdepat"])) {
                $nomdepat = strtoupper(htmlspecialchars(trim($data["nomdepat"])));
                if (empty($nomdepat)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Champ vide département non ajouté"
                    ]);
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM dpatement WHERE nomdepat = :nomdepat");
                        $verrify->execute(
                            array(
                                'nomdepat' => $nomdepat
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "Département existe déjà"
                            ]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO dpatement (nomdepat) VALUES (:nomdepat)");
                            $stmt->execute(
                                array(
                                    "nomdepat" => $nomdepat
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
                    "message" => "aucune donnée reçue"
                ]);
            }
            break;
            
        // Traitement pour supprimer les départements
        case "supprimer":
            if (isset($data["id"])) {
                $id = htmlspecialchars(trim($data["id"]));
                try {
                    $stmt = $pdo->prepare("DELETE FROM dpatement WHERE id_depat = :id");
                    $stmt->execute(
                        array(
                            "id" => $id
                        )
                    );
                    echo json_encode([
                        "success" => true,
                        "message" => "Département supprimé avec succès",
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
                    "message" => "Id de suppression manquant"
                ]);
            }
            break;

        // Traitement pour modifier les départements
        case "modifier":
            if (isset($data["nomdepat"]) && isset($data["id_depat"])) {
                $nomdepat = strtoupper(htmlspecialchars(trim($data["nomdepat"])));
                $id_depat = htmlspecialchars(trim($data["id_depat"]));
                if (empty($nomdepat)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Champ vide département non mofifié"
                    ]);
                } else {
                    try {
                        $verrify = $pdo->prepare("SELECT COUNT(*) FROM dpatement WHERE nomdepat = :nomdepat AND id_depat != :id_depat");
                        $verrify->execute(
                            array(
                                'nomdepat' => $nomdepat,
                                "id_depat" => $id_depat
                            )
                        );
                        $exist = $verrify->fetchColumn();
                        if ($exist) {
                            echo json_encode([
                                "success" => false,
                                "message" => "Département existe déjà"
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE dpatement SET nomdepat = :nomdepat WHERE id_depat = :id_depat");
                            $stmt->execute(
                                array(
                                    "nomdepat" => $nomdepat,
                                    "id_depat" => $id_depat
                                )
                            );
                            echo json_encode([
                                "success" => true,
                                "message" => "Département modifié avec succès",
                                "nomdepat" => $nomdepat
                            ]);
                        }
                    } catch (PDOException $e) {
                        echo json_encode([
                            "success" => false,
                            "message" => "Erreur de connexion : " . $e->getMessage()
                        ]);
                    }
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Données incomplète, modification non éffectuée"
                ]);
            }
            break;
    };
} else {
    echo json_encode([
        "success" => false,
        "message" => "Donnée non reçue ou action manquante"
    ]);
}
