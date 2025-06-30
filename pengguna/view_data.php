<?php include "../template/header.php"; ?>
<div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Data Pengguna
            <?php if ($_SESSION['role'] == "Admin"): ?>
            <a href="?page=pengguna_add" class="btn btn-success btn-sm" style="float:right"><i class="ti ti-plus"></i> Tambah Pengguna</a>
            <?php endif; ?>
            </h5>
            <div class="table-responsive">
              <table class="table table-hover table-bordered">
                  <thead>
                      <tr>
                          <th>No</th>
                          <th>Nama Lengkap</th>
                          <th>Username</th>
                          <th>Role</th>
                          <th>Email Internal</th>
                          <th>No Telepon Internal</th>
                          <th>Status</th>
                          <th>Last Login</th>
                          <?php if ($_SESSION['role'] == "Admin"): ?>
                          <th>Aksi</th>
                          <?php endif; ?>
                      </tr>
                  </thead>
                  <tbody>
                      <?php
                      $no=1;
                      $data = mysqli_query($konek, "SELECT * FROM pengguna ORDER BY created_at DESC");
                      while($row = mysqli_fetch_array($data)){?>
                      <tr>
                          <td><?= $no++ ?></td>
                          <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                          <td><?= htmlspecialchars($row['username']); ?></td>
                          <td><?= htmlspecialchars($row['role']); ?></td>
                          <td><?= htmlspecialchars($row['email_internal']); ?></td>
                          <td><?= htmlspecialchars($row['nomor_telepon_internal']); ?></td>
                          <td>
                              <?php if($row['status_aktif'] == 1): ?>
                                  <span class="badge bg-success">Aktif</span>
                              <?php else: ?>
                                  <span class="badge bg-danger">Tidak Aktif</span>
                              <?php endif; ?>
                          </td>
                          <td><?= $row['last_login'] ? date('d/m/Y H:i', strtotime($row['last_login'])) : '-'; ?></td>
                          <?php if ($_SESSION['role'] == "Admin"): ?>
                          <td>
                              <a href="?page=pengguna_edit&id=<?= $row['id_pengguna']; ?>" class="btn btn-warning btn-sm"><i class="ti ti-edit"></i></a>
                              <a href="?page=pengguna_delete&id=<?= $row['id_pengguna']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="ti ti-trash"></i></a>
                          </td>
                          <?php endif; ?>
                      </tr>
                      <?php    
                      }
                      ?>
                  </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>