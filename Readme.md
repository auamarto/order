## Założenia

- Nie pisałem testów do handlerów gdzie wykorzystuje tylko repository bo nie ma sensu testować tylko tego że wykorzystuje się repository. Testowane powinny być same repository integracyjnie z baza ale nie miałem robić implementacji.
- Applicaiton jest podzielone na Order, Warehouse które odpowiadaja swojej domenie, natomiast Reservation jest bounded contextem (Poczatkowo planowałem tą logike umieścic w Order ale moim zdaniem wygodniej się tego używa w ten sposób)
- Domeny są 3 Order, Warehouse i Reservation. Domena Reservation odpowiada tylko za zapis ale w dalszym procesie przetwarzania zamówienia (zakładam że po opłaceniu albo anulowaniu zamówienia) odpowiadać powinna również za koordynacje zdejmowania rezerwacji w magazynach dla danego zamowienia 