<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="table">

    <table class="pt-table pt-table-1">

        <colgroup>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
            <col></col>
        </colgroup>

        <tr>
            <td class="pt-row-caption"            ><div>      </div></td>
            <td class="pt-col-caption"            ><div>    I </div></td>
            <td class="pt-col-caption"            ><div>   II </div></td>
            <td class="pt-col-caption"            ><div>  III </div></td>
            <td class="pt-col-caption"            ><div>   IV </div></td>
            <td class="pt-col-caption"            ><div>    V </div></td>
            <td class="pt-col-caption"            ><div>   VI </div></td>
            <td class="pt-col-caption"            ><div>  VII </div></td>
            <td class="pt-col-caption" colspan="3"><div> VIII </div></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption"><div>1</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='H']"  /></td>
            <td colspan="6"></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='He']" /></td>
            <td colspan="2" rowspan="3"></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption"><div>2</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Li']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Be']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='B']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='C']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='N']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='O']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='F']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ne']" /></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption"><div>3</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Na']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Mg']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Al']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Si']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='P']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='S']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cl']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ar']" /></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption" rowspan="2"><div>4</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='K']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ca']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Sc']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ti']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='V']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cr']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Mn']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Fe']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Co']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ni']" /></td>
        </tr>
        <tr>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cu']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Zn']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ga']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ge']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='As']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Se']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Br']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Kr']" /></td>
            <td class="pt-elem" colspan="2"></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption" rowspan="2"><div>5</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Rb']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Sr']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Y']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Zr']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Nb']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Mo']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Tc']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ru']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Rh']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Pd']" /></td>
        </tr>
        <tr>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ag']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cd']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='In']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Sn']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Sb']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Te']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='I']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Xe']" /></td>
            <td class="pt-elem" colspan="2"></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption" rowspan="2"><div>6</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cs']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ba']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='La']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Hf']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ta']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='W']"  /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Re']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Os']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ir']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Pt']" /></td>
        </tr>
        <tr>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Au']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Hg']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Tl']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Pb']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Bi']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Po']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='At']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Rn']" /></td>
            <td class="pt-elem" colspan="2"></td>
        </tr>
    
        <tr>
            <td class="pt-row-caption" rowspan="2"><div>7</div></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Fr']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ra']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ac']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Rf']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Db']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Sg']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Bh']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Hs']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Mt']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ds']" /></td>
        </tr>
        <tr>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Rg']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Cn']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Nh']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Fl']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Mc']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Lv']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Ts']" /></td>
            <td class="pt-elem"><xsl:apply-templates select="item[./sign='Og']" /></td>
            <td class="pt-elem" colspan="2"></td>
        </tr>
    </table>
    
    <table class="pt-table pt-table-2">
        <tr>
            <td><xsl:apply-templates select="item[./sign='La']" /></td>
            <td><xsl:apply-templates select="item[./sign='Ce']" /></td>
            <td><xsl:apply-templates select="item[./sign='Pr']" /></td>
            <td><xsl:apply-templates select="item[./sign='Nd']" /></td>
            <td><xsl:apply-templates select="item[./sign='Pm']" /></td>
            <td><xsl:apply-templates select="item[./sign='Sm']" /></td>
            <td><xsl:apply-templates select="item[./sign='Eu']" /></td>
            <td><xsl:apply-templates select="item[./sign='Gd']" /></td>
            <td><xsl:apply-templates select="item[./sign='Tb']" /></td>
            <td><xsl:apply-templates select="item[./sign='Dy']" /></td>
            <td><xsl:apply-templates select="item[./sign='Ho']" /></td>
            <td><xsl:apply-templates select="item[./sign='Er']" /></td>
            <td><xsl:apply-templates select="item[./sign='Tm']" /></td>
            <td><xsl:apply-templates select="item[./sign='Yb']" /></td>
            <td><xsl:apply-templates select="item[./sign='Lu']" /></td>
        </tr>
    </table>

    <table class="pt-table pt-table-2">
        <tr>
            <td><xsl:apply-templates select="item[./sign='Ac']" /></td>
            <td><xsl:apply-templates select="item[./sign='Th']" /></td>
            <td><xsl:apply-templates select="item[./sign='Pa']" /></td>
            <td><xsl:apply-templates select="item[./sign='U']"  /></td>
            <td><xsl:apply-templates select="item[./sign='Np']" /></td>
            <td><xsl:apply-templates select="item[./sign='Pu']" /></td>
            <td><xsl:apply-templates select="item[./sign='Am']" /></td>
            <td><xsl:apply-templates select="item[./sign='Cm']" /></td>
            <td><xsl:apply-templates select="item[./sign='Bk']" /></td>
            <td><xsl:apply-templates select="item[./sign='Cf']" /></td>
            <td><xsl:apply-templates select="item[./sign='Es']" /></td>
            <td><xsl:apply-templates select="item[./sign='Fm']" /></td>
            <td><xsl:apply-templates select="item[./sign='Md']" /></td>
            <td><xsl:apply-templates select="item[./sign='No']" /></td>
            <td><xsl:apply-templates select="item[./sign='Lr']" /></td>
        </tr>
    </table>

</xsl:template>

        <!-- <xsl:apply-templates select="item" /> -->

<xsl:template match="item">
	<div class="pt-item" data-color="{color}" data-number="{number}">
        <div class="pt-item-sign">
            <xsl:value-of select="sign" />
        </div>
        <div class="pt-item-number">
            <xsl:value-of select="number" />
        </div>
        <div class="pt-item-mass">
            <xsl:value-of select="format-number(translate(translate(mass, ']', ''), '[', ''), '#.00')" />
        </div>
        <div class="pt-item-title">
            <xsl:value-of select="title_ru" />
        </div>
	</div>
</xsl:template>

</xsl:stylesheet>