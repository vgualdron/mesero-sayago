<template>
  <div>
    <b-row>
      <b-col cols="12" v-if="mesa">
        <b-card v-if="pedido && pedido != null" :header="cardHeader" tag="article" class="m-3 mt-3" border-variant="primary" header-bg-variant="primary">
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-form-group>
                <b-btn
                    class="ml-3 mb-3 float-right"
                    @click.stop="anularPedido()"
                    variant="primary">Anular Pedido</b-btn>
                <b-btn
                  class="ml-3 mb-3 float-right"
                  @click.stop="abrirModalCambiarMesa()"
                  variant="primary">Cambiar de Mesa</b-btn>
                <b-btn
                  v-if="pedido && pedido.editablepedido == 'SI'"
                  class="ml-3 mb-3 float-right"
                  @click.stop="cargarFormulario(null,'Agregar')"
                  variant="primary">Agregar Producto</b-btn>
                <b-btn
                  v-if="(pedido && pedido.editablepedido == 'SI') && (items && items.length > 0)"
                  class="ml-3 mb-3 float-right"
                  @click.stop="enviarPedido()"
                  variant="primary"><template v-if="(pedido && pedido.idestado > 1)"> Volver a </template>Enviar Pedido</b-btn>
                <b-btn
                  v-if="(pedido && pedido.editablepedido == 'SI') && (items && items.length > 0) && (pedido && pedido.idestado > 1)"
                  class="ml-3 mb-3 float-right"
                  @click.stop="facturarPedido()"
                  variant="primary">Generar Factura</b-btn>
              </b-form-group>
            </b-col>
          </b-row>

          <b-row align-h="center">
            <b-col class="contenedor-tabla">
              <b-form-group
                id="nombreCliente"
                description="Solo si es necesario."
                label="<strong>Escriba el Nombre del Cliente</strong>">
                <b-form-input id="input-cliente" v-model="nombreCliente" trim></b-form-input>
              </b-form-group>
              <b-table
                v-if="items && items.length > 0"
                stacked="sm"
                striped
                hover
                class="columna-centrada"
                tbody-class="columna-centrada"
                :fields="campos"
                :items="items">
                <template slot="acciones" slot-scope="row">
                  <b-button
                    style="margin: 1px;"
                    class="ml-2"
                    @click.stop="cargarFormulario(row.item,'Ver')">
                    <i class="icon-eye"></i>
                  </b-button>
                  <b-button
                    v-if="pedido && pedido.editablepedido == 'SI'"
                    style="margin: 1px;"
                    class="ml-2"
                    variant="primary"
                    @click.stop="cargarFormulario(row.item,'Modificar')">
                    <i class="icon-pencil"></i>
                  </b-button>
                  <b-button
                    v-if="pedido && pedido.editablepedido == 'SI'"
                    style="margin: 1px;"
                    class="ml-2"
                    variant="danger"
                    @click.stop="cargarFormulario(row.item,'Eliminar')">
                    <i class="fa fa-trash"></i>
                  </b-button>
                </template>
              </b-table>
              <b-row v-if="items && items.length > 0" class="mt-1 mb-2 text-center">
                <b-col cols="12">
                  <b-alert show><strong>TOTAL: {{totalPedido()}}</strong></b-alert>
                </b-col>
              </b-row>
              <b-row v-else class="mt-1 mb-2 text-center">
                <b-col cols="12">
                  <b-alert show>El pedido está creado, pero no se encontraron registros de productos pedidos. Haz click en el boton Nuevo para agregar productos.</b-alert>
                  <b-btn
                    class="ml-3 float-right"
                    @click.stop="anularPedido()"
                    variant="primary">Anular Pedido</b-btn>
                </b-col>
              </b-row>
            </b-col>
          </b-row>

        </b-card>
        <b-card v-else :header="cardHeader" tag="article" class="m-3 mt-3" border-variant="primary" header-bg-variant="primary">
          <b-row class="mt-1 mb-2 text-center">
            <b-col cols="12">
              <b-alert show variant="info">No ha creado el pedido. Haz click en el boton Crear Pedido para iniciar.</b-alert>
               <b-btn
                class="ml-3 float-right"
                @click.stop="crearPedido()"
                variant="primary"
              >Crear Pedido</b-btn>
            </b-col>
          </b-row>
         
        </b-card>  
      </b-col>
      <b-col cols="12" v-else>
        <b-card tag="article" class="m-3 mt-3">
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-alert show variant="danger">No ha seleccionado mesa.</b-alert>
            </b-col>
          </b-row>

        </b-card>
      </b-col>
    </b-row>

    <b-modal v-if="objeto" centered v-model="showModal" :title="tipoOperacion">
      <b-container>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Tipo Producto
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripciontipoproducto}}
            </span>
            <b-form-select v-else v-model="objeto.idtipoproducto" class="mb-3">
              <option :key="'tipo_' + t" v-for="(tipo, t) in tipoProductos" :value="tipo.id">{{tipo.descripcion}}</option>
            </b-form-select>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;">* </span>Producto
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripcionproducto}}
            </span>
            <b-form-select v-else v-model="objeto.idproducto" class="mb-3">
              <template v-for="(tipo, t) in tipoProductos">
                
                  <template v-if="objeto && objeto.idtipoproducto">
                    <template v-for="(producto, p) in tipo.productos">
                      <option v-if="producto.idtipoproducto == objeto.idtipoproducto" :key="'prod_' + t + '_' + p" :value="producto.id">{{producto.descripcion}}</option>
                    </template>
                  </template>
                  <template v-else>
                    <template v-for="(producto, p) in tipo.productos">
                      <option :key="'prod_' + t + '_' + p" :value="producto.id">{{producto.descripcion}}</option>
                    </template>
                  </template>
                
              </template>
            </b-form-select>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;">* </span>Cantidad
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.cantidadproducto}}
            </span>
            <b-form-input
              v-else
              type="number"
              required
              v-model="objeto.cantidadproducto"
              class="form-control"/>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Observación
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripcion}}
            </span>
            <b-form-input
              v-else
              type="text"
              required
              v-model="objeto.descripcion"
              class="form-control"/>
          </b-col>
        </b-row>
      </b-container>
      <div slot="modal-footer" v-if="objeto !== null" class="pull-right">
        <b-btn
          v-if="tipoOperacion === 'Ver'"
          size="sm"
          variant="secondary"
          @click.stop="cerrarFormulario">Cerrar</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Agregar'"
          size="sm"
          variant="primary"
          @click.stop="guardar">Guardar</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Modificar'"
          size="sm"
          variant="primary"
          @click.stop="guardarCambios">Guardar Cambios</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Eliminar'"
          size="sm"
          variant="danger"
          @click.stop="eliminar">Eliminar</b-btn>
      </div>
    </b-modal>


    <b-modal centered v-model="showModalCambiarMesa" title="Cambiar de Mesa">
      <b-container>
        <b-row class="mb-3">
          <b-col class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Seleccione la nueva mesa
            </span>:<br>
            <b-form-select  v-model="idMesaNueva" class="mb-3">
              <template v-for="(mesa, m) in mesasDisponibles" >
                <option :key="'mesa_' + m" :value="mesa.id" v-if="mesa.descripcionestadopedido == null">{{mesa.descripcion}}</option>
              </template>
            </b-form-select>
          </b-col>
        </b-row>
      </b-container>
      <div slot="modal-footer" class="pull-right">
        <b-btn
          variant="primary"
          @click.stop="cambiarMesa">Cambiar de Mesa</b-btn>
      </div>
    </b-modal>

  </div>
</template>
<style scoped>
.columna-centrada {
  text-align: center;
}
.mesa {
  cursor: pointer;
}
.border-td {
  border: 1px solid #000;
  padding: 5px;
}
</style>

<script>
var self = this;
export default {
  name: "DetallePedido",
  data: function() {
    return {
      pedido: null,
      mesa: this.$route.params.mesa ? this.$route.params.mesa : null,
      idPedido: this.$route.params.idPedido ? this.$route.params.idPedido : null,
      cardHeader: '<strong> Detalles del pedido, ' + (this.$route.params.mesa && this.$route.params.mesa.descripcion ? this.$route.params.mesa.descripcion : '') + ' ( ' + (this.pedido && this.pedido.descripcionestado ? this.pedido.descripcionestado : '') + ' )' +' </strong>',
      showModal: false,
      showModalCambiarMesa: false,
      tipoProductos: [],
      campos: [
        {
          key: "cantidadproducto",
          label: "Cantidad",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        },
        {
          key: "descripcionproducto",
          label: "Producto",
          sortable: true,
          thStyle: "text-align:center;"
        },
        {
          key: "precioproducto",
          label: "Precio U.",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        },
        {
          key: "acciones",
          label: "Acciones",
          sortable: false,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        }
      ],
      items: [],
      objeto: null,
      cantidadProductoVieja: 0,
      idMesaNueva: null,
      mesasDisponibles: [],
      nombreCliente: null
    };
  },
  methods: {
    atras: function() {
      this.$router.go(-1)
    },
    listarTiposPedido: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {};
      this.$http.post("ws/productotipoproducto/", frm).then(resp => {
        self.tipoProductos = resp.data.tiposProducto;
        self.$loader.close();
      });
    },
    abrirModal: function() {
      this.showModal = true
    },
    cerrarModal: function() {
      this.showModal = false
    },
    cargarFormulario: function(obj, operacion) {
      this.tipoOperacion = operacion;
      this.objeto = obj;
      if (obj === null) {
        this.objeto = {
          id: null,
          idtipoproducto: null,
          descripciontipoproducto: null,
          idproducto: null,
          descripcionproducto: null,
          costoproducto: null,
          cantidadproducto: null,
          precioproducto: null,
          descripcion: null
        };
      } else {
        this.selectedVisble = obj.visible
        this.cantidadProductoVieja = obj.cantidadproducto;
        this.objeto = {
          id: obj.id,
          idtipoproducto: obj.idtipoproducto,
          descripciontipoproducto: obj.descripciontipoproducto,
          idproducto: obj.idproducto,
          descripcionproducto: obj.descripcionproducto,
          costoproducto: obj.costoproducto,
          cantidadproducto: obj.cantidadproducto,
          precioproducto: obj.precioproducto,
          descripcion: obj.descripcion
        };
      }
      this.showModal = true;
    },
    validarCampos: function() {
      if (!this.objeto.idproducto || this.objeto.idproducto < 1) {
        this.$toast.error("Debe seleccionar un producto.");
        return false;
      }
      if (!this.objeto.cantidadproducto || this.objeto.cantidadproducto < 1) {
        this.$toast.error("Debe de escribir la cantidad.");
        return false;
      }
      return true;
    },
    crearPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        idmesa: self.mesa.id,
        token: token
      };
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea crear un nuevo pedido?",
          function() {
            self.$loader.open({ message: "Creando ..." });
            self.$http.post("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.idPedido = respuesta.id;
              self.consultarPedido(self.idPedido);
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo crear el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    consultarPedido: function(id) {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        params: {
          id: id,
          token: token
        }
      };
      self.$loader.open({ message: "Cargando ..." });
      self.$http.get("ws/pedido/", frm).then(resp => {
        var respuesta = resp.data;
        self.pedido = respuesta;
        self.cardHeader = '<strong> Detalles del pedido, ' + (this.$route.params.mesa && this.$route.params.mesa.descripcion ? this.$route.params.mesa.descripcion : '') + ' ( ' + (this.pedido && this.pedido.descripcionestado ? this.pedido.descripcionestado : '') + ' )' +' </strong>'
        self.$loader.close();
        self.listar();
      }).catch(resp => {
        self.$loader.close();
        if (resp.data && resp.data.mensaje) {
          self.$toast.error(resp.data.mensaje);
        } else {
          self.$toast.error("No se pudo consultar el pedido");
        }
      });
    },
    anularPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "6",
        idmesa: self.pedido.idmesa
      };
      this.$alertify
        .confirmWithTitle(
          "Anular",
          "Seguro que desea anular el pedido?",
          function() {
            self.$loader.open({ message: "Anulando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo anular el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    abrirModalCambiarMesa: function() {
      this.listarMesasDisponibles()
      this.showModalCambiarMesa = true
    },
    cambiarMesa: function() {
      var self = this;
      if (!self.idMesaNueva) {
        self.$toast.error("Debe de seleccionar la mesa nueva");
        return false;
      }
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: self.pedido.idestado,
        idmesa: self.idMesaNueva
      };
      this.$alertify
        .confirmWithTitle(
          "Cambiar de Mesa",
          "Seguro que desea cambiar de mesa?",
          function() {
            self.$loader.open({ message: "Cambiando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo anular el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    enviarPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "2",
        idmesa: self.pedido.idmesa
      };
      this.$alertify
        .confirmWithTitle(
          "Enviar",
          "Seguro que desea enviar el pedido?",
          function() {
            self.$loader.open({ message: "Enviando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.generarTicketPedido();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo enviar el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    facturarPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "5",
        idmesa: self.pedido.idmesa,
        cliente: self.nombreCliente
      };
      this.$alertify
        .confirmWithTitle(
          "Facturar",
          "Seguro que desea facturar el pedido?",
          function() {
            self.$loader.open({ message: "Facturando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.generarTicketFactura();
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo facturar el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    listar: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = { params: {
          idpedido: self.pedido.id
        }
      };
      this.$http.get("ws/detallepedido/", frm).then(resp => {
          self.items = resp.data;
          if (self.items && self.items.length > 0) {
            self.totalFilas = self.items.length;
          }
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items de detalles del pedido.");
          }
        });
    },
    guardar: function() {
      if(!this.validarCampos()) {
        return false
      }
      var self = this;
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      self.$set(self.objeto, "idpedido", this.pedido.id);
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea guardar el nuevo registro?",
          function() {
            self.$loader.open({ message: "Guardando ..." });
            self.$http.post("ws/detallepedido/", self.objeto).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
                self.showModal = false;
              })
              .catch(resp => {
                self.$loader.close();
                if (resp.data && resp.data.Error) {
                  self.$toast.error(resp.data.Error);
                } else {
                  self.$toast.error("error registrando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    guardarCambios: function() {
      if(!this.validarCampos()) {
        return false
      }
      var self = this;
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      self.$set(self.objeto, "idpedido", this.pedido.id);
      self.$set(self.objeto, "cantidadproductosumar", (parseInt(self.cantidadProductoVieja) - parseInt(self.objeto.cantidadproducto)));
      this.$alertify
        .confirmWithTitle(
          "Modificar",
          "Seguro que desea modificar el registro?",
          function() {
            self.showModal = false;
            self.$loader.open({ message: "Guardando Cambios ..." });
            self.$http.put("ws/detallepedido/", self.objeto).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
              })
              .catch(resp => {
                self.$loader.close();
                self.showModal = true;
                if (resp.data && resp.data.mensaje) {
                  self.$toast.error(resp.data.mensaje);
                } else {
                  self.$toast.error("error modificando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    eliminar: function() {
      var self = this;
      let token = window.localStorage.getItem("token");
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      this.$alertify
        .confirmWithTitle(
          "Eliminar",
          "Seguro que desea eliminar el registro?",
          function() {
            self.showModal = false;
            self.$loader.open({ message: "Eliminando ..." });
            self.$http.delete("ws/detallepedido/", {params: {id: self.objeto.id, token: token}}).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
              })
              .catch(resp => {
                self.$loader.close();
                self.showModal = true;
                if (resp.data && resp.data.mensaje) {
                  self.$toast.error(resp.data.mensaje);
                } else {
                  self.$toast.error("error eliminando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    cerrarFormulario: function() {
      this.showModal = false;
    },
    actualizarTabla: function(itemsFiltrados) {
      // this.totalFilas = itemsFiltrados.length;
      // this.paginaActual = 1;
    },
    generarTicketPedido: function() {
      var self = this;
      var frm = { 
        productos: self.items,
        mesa: self.mesa,
        cliente: self.nombreCliente,
        mesero: self.pedido.mesero
      }
      self.$loader.open({ message: "Generando ..." });
      self.$http.post("ws/ticket/pedido.php", frm).then(resp => {
          self.$loader.close();
          // self.$toast.success(resp.data.mensaje);
          // self.$toast.success("Exito");
        })
        .catch(resp => {
          self.$loader.close();
          self.$toast.error("error generando ticket de pedido");
        });
    },
    generarTicketFactura: function() {
      var self = this;
      var frm = { 
        productos: self.items,
        mesa: self.mesa,
        cliente: self.nombreCliente,
        mesero: self.pedido.mesero
      }
      self.$loader.open({ message: "Generando ..." });
      self.$http.post("ws/ticket/factura.php", frm).then(resp => {
          self.$loader.close();
          // self.$toast.success(resp.data.mensaje);
          // self.$toast.success("Exito");
        })
        .catch(resp => {
          self.$loader.close();
          self.$toast.error("error generando ticket de factura");
        });
    },
    totalPedido: function() {
      var self = this;
      var total = 0;
      this.items.forEach(item => {
        total += (parseInt(item.precioproducto) * parseInt(item.cantidadproducto));
      });
      var total = '$' + Number(total.toFixed(1)).toLocaleString();
      return total;
    },
    listarMesasDisponibles: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = { params: {
        }
      };
      this.$http.get("ws/mesa/", frm).then(resp => {
          self.mesasDisponibles = resp.data;
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items de las mesas disponibles.");
          }
        });
    },
  },
  created: function() {
    if (this.idPedido) {
      this.consultarPedido(this.idPedido);
    }
    this.listarTiposPedido();
  },
  mounted: function() {
    this.$loader.close();
  }
};
</script>
