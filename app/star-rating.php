<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
/* star-rating.php - for items rating
 * called from post request from editDB
 */

require_once '../inc/common.php';

if (isset($_POST['star']) &&
    isset($_POST['item_id']) &&
    is_pos_int($_POST['star']) &&
    is_pos_int($_POST['item_id'])) {

    $sql = 'UPDATE items SET rating = :rating WHERE id = :id';
    $req = $pdo->prepare($sql);
    $req->bindParam(':rating', $_POST['star'], PDO::PARAM_INT);
    $req->bindParam(':id', $_POST['item_id'], PDO::PARAM_INT);
    $req->execute();
}
